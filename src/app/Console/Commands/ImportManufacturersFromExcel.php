<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Models\Manufacturer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportManufacturersFromExcel extends Command
{
    protected $signature = 'mmc:import-manufacturers
        {--path=/mnt/data/mmc_manufacturer_map_list.xls : Path to the Excel file}
        {--sheet= : Sheet name (optional; default = first)}
        {--reset : Delete all existing manufacturers before import}
    ';

    protected $description = 'Import manufacturers / facilities from an Excel map list';

    public function handle(): int
    {
        $path = $this->option('path');
        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        if ($this->option('reset')) {
            Manufacturer::truncate();
            $this->warn('Reset: deleted all rows from manufacturers.');
        }

        // Read workbook -> arrays (works for xls/xlsx)
        $sheets = Excel::toArray(null, $path);
        if (!$sheets || !is_array($sheets) || count($sheets) === 0) {
            $this->error('No sheets found in Excel.');
            return self::FAILURE;
        }

        $sheetName = $this->option('sheet');
        $rows = $sheetName ? $this->findSheetByName($path, $sheetName) : $sheets[0];

        if (!$rows || count($rows) === 0) {
            $this->error('Selected sheet is empty or missing.');
            return self::FAILURE;
        }

        // Normalize header row
        $header = array_map(
            fn($h) => Str::of((string)$h)->lower()->replace(['  ', "\n", "\r", "\t"], ' ')->trim()->toString(),
            array_shift($rows)
        );

        // Build a header index map (case-insensitive) and allow aliases
        $index = fn(array $aliases) => $this->findHeaderIndex($header, $aliases);

        $col = [
            'name'               => $index(['company','name','manufacturer','organisation','organization']),
            'mmc_method'         => $index(['mmc method','mmc_method','method','system','system code','system_code']),
            'product_category'   => $index(['category','product category','system category','system_category']),
            'product_subcategory'=> $index(['subcategory','sub-category','product subcategory','product_subcategory']),
            'address'            => $index(['address','full address','location']),
            'county'             => $index(['county','region','area']),
            'country'            => $index(['country']),
            'website'            => $index(['website','url','web']),
            'phone'              => $index(['phone','telephone','tel','mobile']),
            'email'              => $index(['email','e-mail']),
            'lat'                => $index(['latitude','lat']),
            'lng'                => $index(['longitude','lng','long']),
            'notes'              => $index(['notes','comment','comments','remarks']),
            'status'             => $index(['status','active','is_active']),
        ];

        $upserts = 0; $skipped = 0; $total = 0;

        foreach ($rows as $r) {
            $total++;

            $name = $this->val($r, $col['name']);
            if ($name === '') { $skipped++; continue; } // need at least a name

            $mmc = $this->val($r, $col['mmc_method']);
            $cat = $this->val($r, $col['product_category']);
            $sub = $this->val($r, $col['product_subcategory']);
            $addr = $this->val($r, $col['address']);
            $countyName = $this->val($r, $col['county']);
            $country = $this->val($r, $col['country']) ?: 'Ireland';
            $website = $this->val($r, $col['website']);
            $phone = $this->val($r, $col['phone']);
            $email = $this->val($r, $col['email']);

            $lat = $this->num($r, $col['lat']);
            $lng = $this->num($r, $col['lng']);

            $notes = $this->val($r, $col['notes']);
            $status = $this->val($r, $col['status']);

            // Try map county name to counties.code
            $countyCode = null;
            if ($countyName !== '') {
                $countyCode = optional(
                    County::query()
                        ->where('name', $countyName)
                        ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($countyName)])
                        ->first()
                )->code;
            }

            // Collect any extra columns as properties
            $properties = $this->collectExtras($header, $r, array_filter($col, fn($i) => $i !== null));

            // Upsert key: (name, county_name, product_category)
            Manufacturer::updateOrCreate(
                [
                    'name' => $name,
                    'county_name' => $countyName ?: null,
                    'product_category' => $cat ?: null,
                ],
                [
                    'mmc_method'         => $mmc ?: null,
                    'product_subcategory'=> $sub ?: null,
                    'address'            => $addr ?: null,
                    'county_code'        => $countyCode,
                    'country'            => $country ?: null,
                    'website'            => $website ?: null,
                    'phone'              => $phone ?: null,
                    'email'              => $email ?: null,
                    'lat'                => $lat,
                    'lng'                => $lng,
                    'properties'         => $properties ?: null,
                    'source'             => basename($path),
                    'is_active'          => $this->toBool($status, true),
                ]
            );

            $upserts++;
        }

        $this->info("Processed: {$total}. Upserted: {$upserts}. Skipped (no name): {$skipped}.");
        return self::SUCCESS;
    }

    /** Helpers */

    private function findSheetByName(string $path, string $sheetName): ?array
    {
        // Maatwebsite doesn’t expose sheet names in toArray(); simplest approach is to load all and pick by index if needed.
        // If you truly need by name, swap to a custom importable. For now assume first sheet when name not resolvable.
        $all = Excel::toArray(null, $path);
        return $all[0] ?? null;
    }

    private function findHeaderIndex(array $header, array $aliases): ?int
    {
        $aliases = array_map(fn($a) => mb_strtolower($a), $aliases);
        foreach ($header as $i => $h) {
            $h = mb_strtolower(trim((string)$h));
            if (in_array($h, $aliases, true)) return $i;
        }
        // also try loose contains (e.g., "company name")
        foreach ($header as $i => $h) {
            $hlo = mb_strtolower((string)$h);
            foreach ($aliases as $a) {
                if (str_contains($hlo, $a)) return $i;
            }
        }
        return null;
    }

    private function val(array $row, ?int $idx): string
    {
        if ($idx === null) return '';
        $v = $row[$idx] ?? '';
        return is_string($v) ? trim($v) : trim((string)$v);
    }

    private function num(array $row, ?int $idx): ?float
    {
        if ($idx === null) return null;
        $v = $row[$idx] ?? null;
        if ($v === '' || $v === null) return null;
        if (is_numeric($v)) return (float)$v;
        // Try to coerce “52,1234”
        $v = str_replace([','], ['.'], (string)$v);
        return is_numeric($v) ? (float)$v : null;
    }

    private function toBool(?string $val, bool $default = true): bool
    {
        $v = mb_strtolower(trim((string)$val));
        if ($v === '') return $default;
        return in_array($v, ['1','true','yes','y','active','enabled'], true);
    }

    private function collectExtras(array $header, array $row, array $usedMap): array
    {
        $used = array_values($usedMap); // indices used
        $props = [];
        foreach ($row as $i => $v) {
            if (in_array($i, $used, true)) continue;
            $key = isset($header[$i]) ? (string)$header[$i] : "col_{$i}";
            $key = Str::of($key)->lower()->replace(['  ', "\n", "\r", "\t"], ' ')->trim()->toString();
            $props[$key] = $v;
        }
        return $props;
    }
}