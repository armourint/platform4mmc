<?php

namespace App\Console\Commands;

use App\Models\Manufacturer;
use App\Services\Geo\CoordinateConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportManufacturersFromExcel extends Command
{
    protected $signature = 'mmc:import-manufacturers
        {--path= : Path to CSV/XLSX}
        {--reset : Truncate manufacturers before import}';

    protected $description = 'Import manufacturers; supports Web Mercator (POINT_X/POINT_Y) -> WGS84.';

    public function handle(): int
    {
        $path = (string) $this->option('path');
        if ($path === '') {
            $this->error('--path is required');
            return self::FAILURE;
        }

        $fullPath = Storage::exists($path) ? Storage::path($path) : $path;
        if (!is_file($fullPath)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        if ($this->option('reset')) {
            Manufacturer::query()->truncate();
            $this->info('manufacturers table truncated.');
        }

        $rows = $this->readSpreadsheet($fullPath);
        $count = 0;

        foreach ($rows as $row) {
            if (!is_array($row) || $row === []) continue;

            // normalise headers (space / case)
            $norm = [];
            foreach ($row as $k => $v) {
                $kk = trim(mb_strtolower(is_string($k) ? $k : (string)$k));
                $kk = str_replace([' ', "\t"], '_', $kk); // e.g. "mmc method" -> "mmc_method"
                $norm[$kk] = $v;
            }

            // Required name
            $name = trim((string)($norm['manufacturer'] ?? $norm['name'] ?? ''));
            if ($name === '') continue;

            // Map fields from your sheet to model
            $mmcMethod   = trim((string)($norm['mmc_method'] ?? ''));        // e.g. "LGS (Light Gauge Steel)"
            $address     = trim((string)($norm['address'] ?? ''));
            $countyName  = trim((string)($norm['county'] ?? $norm['county_name'] ?? ''));
            $website     = trim((string)($norm['website'] ?? ''));
            $isActive    = true; // no column in sample; default true

            // Derive lat/lng:
            // 1) Use existing lat/lng if provided
            $lat = $this->numOrNull($norm['lat'] ?? null);
            $lng = $this->numOrNull($norm['lng'] ?? null);

            // 2) Otherwise, use Web Mercator POINT_X/POINT_Y
            if ($lat === null || $lng === null) {
                $x = $this->numOrNull($norm['point_x'] ?? null);
                $y = $this->numOrNull($norm['point_y'] ?? null);

                if ($x !== null && $y !== null) {
                    try {
                        [$lat, $lng] = CoordinateConverter::webMercatorToWgs84($x, $y);
                    } catch (\Throwable $e) {
                        $this->warn("WebMercator->WGS84 failed for '{$name}': {$e->getMessage()}");
                        $lat = $lng = null;
                    }
                }
            }

            // Natural key: (name, county_name) if county present, else name
            $key = ['name' => $name];
            if ($countyName !== '') {
                $key['county_name'] = $countyName;
            }

            // Persist
            $record = Manufacturer::updateOrCreate(
                $key,
                [
                    'mmc_method'         => $mmcMethod ?: null,
                    'product_category'    => null, // not in your file
                    'product_subcategory' => null, // not in your file
                    'address'             => $address ?: null,
                    'county_code'         => null, // not in your file
                    'county_name'         => $countyName !== '' ? $countyName : null,
                    'country'             => 'Ireland', // adjust if needed
                    'website'             => $website ?: null,
                    'phone'               => null, // not in your file
                    'email'               => null, // not in your file
                    'lat'                 => $lat,
                    'lng'                 => $lng,
                    'properties'          => $this->safeJson($row), // original row (un-normalised keys)
                    'source'              => basename($fullPath),
                    'is_active'           => (bool)$isActive,
                ]
            );

            $count++;
        }

        $this->info("Imported/updated {$count} manufacturers.");
        return self::SUCCESS;
    }

    private function numOrNull($v): ?float
    {
        if ($v === '' || $v === null) return null;
        if (is_string($v)) $v = str_replace([','], [''], $v);
        return is_numeric($v) ? (float)$v : null;
    }

    private function safeJson($row): array
    {
        // Keep a trimmed snapshot of the original row as properties
        $out = [];
        foreach ((array)$row as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $out[(string)$k] = is_string($v) ? trim($v) : $v;
            } else {
                $out[(string)$k] = $v;
            }
        }
        return $out;
    }

    /**
     * CSV/XLSX reader (no external HTTP).
     */
    private function readSpreadsheet(string $fullPath): array
    {
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $f = fopen($fullPath, 'r');
            $headers = [];
            $rows = [];
            if ($f) {
                if (($h = fgetcsv($f)) !== false) {
                    $headers = array_map(fn($x)=> (string)$x, $h);
                }
                while (($r = fgetcsv($f)) !== false) {
                    $rows[] = @array_combine($headers, $r) ?: [];
                }
                fclose($f);
            }
            return $rows;
        }

        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            $ss = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            // Your sample has one sheet: "Steelfactories.shp"
            $sheet = $ss->getActiveSheet();
            $rows = [];
            $header = [];
            foreach ($sheet->toArray(null, true, true, true) as $i => $row) {
                $row = array_values($row);
                if ($i === 1) {
                    $header = array_map(fn($x)=> (string)$x, $row);
                    continue;
                }
                if (!$header) continue;
                $assoc = [];
                foreach ($row as $idx => $val) {
                    $key = $header[$idx] ?? "col{$idx}";
                    $assoc[$key] = $val;
                }
                // Skip completely empty rows
                if (count(array_filter($assoc, fn($v)=> $v !== null && $v !== '')) === 0) {
                    continue;
                }
                $rows[] = $assoc;
            }
            return $rows;
        }

        throw new \RuntimeException('No spreadsheet reader available. Install phpoffice/phpspreadsheet or use CSV.');
    }
}
