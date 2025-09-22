<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Models\EnvironmentalFactor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportEnvironmentalKpiFromExcel extends Command
{
    // IMPORTANT: flat, single-line signature (no newlines!)
    protected $signature = 'mmc:import-env-kpi {--path=} {--sheet=} {--dataset-version=v2025.09}';

    protected $description = 'Import MVP environmental KPIs (A1–A3 and A4 per m²) into an Environmental dataset';

    public function handle(): int
    {
        $this->info('DEBUG: entered handle()'); // prove we get here

        $path = $this->option('path') ?: base_path('ireland_generic_mmc_model.xlsx');
        if (!is_file($path)) {
            $this->error("File not found: $path");
            return self::FAILURE;
        }

        $this->info('Opening spreadsheet: '.$path);

        // Load workbook and pick sheet
        $spreadsheet = IOFactory::load($path);
        $sheetName   = $this->option('sheet');
        $sheet = $sheetName
            ? $spreadsheet->getSheetByName($sheetName)
            : $spreadsheet->getSheet(0);

        if (!$sheet) {
            $this->error('Could not find the target sheet. (Name: '.($sheetName ?: 'first sheet').')');
            return self::FAILURE;
        }

        $rows = $sheet->toArray(null, true, true, true);
        if (empty($rows) || count($rows) < 2) {
            $this->error('No data rows found (need a header row + at least one data row).');
            return self::FAILURE;
        }

        // Header
        $headerRow = array_shift($rows);
        $header = [];
        foreach ($headerRow as $col => $value) {
            $key = strtolower(trim((string)$value));
            $header[$col] = $key;
        }

        // Flexible header matching
        $find = function(string $needle) use ($header) {
            foreach ($header as $col => $name) {
                if ($name === $needle) return $col;
            }
            return null;
        };

        // Expected: system_code | a1-a3_per_m2 | a4_per_m2 (case-insensitive; tolerate variants)
        $colSystem = $find('system_code') ?? $find('system');
        $colA1A3   = $find('a1-a3_per_m2') ?? $find('a1a3_per_m2') ?? $find('a1-a3/m2') ?? $find('a1a3/m2');
        $colA4     = $find('a4_per_m2')    ?? $find('a4/m2');

        if (!$colSystem) {
            $this->error('Missing required "system_code" (or "system") column in header.');
            $this->line('Header columns detected: '.implode(', ', array_values($header)));
            return self::FAILURE;
        }

        $version = $this->option('version');
        $dataset = DatasetVersion::firstOrCreate(
            ['module' => 'environmental', 'version_label' => $version],
            ['status' => 'draft', 'payload' => []]
        );

        $imported = 0;
        DB::transaction(function () use ($rows, $colSystem, $colA1A3, $colA4, $dataset, &$imported) {
            foreach ($rows as $row) {
                $code = strtoupper(trim((string)($row[$colSystem] ?? '')));
                if ($code === '') continue;

                $a1a3 = $colA1A3 ? $row[$colA1A3] : null;
                $a4   = $colA4   ? $row[$colA4]   : null;

                $a1a3 = is_numeric($a1a3) ? (float)$a1a3 : null;
                $a4   = is_numeric($a4)   ? (float)$a4   : null;

                EnvironmentalFactor::updateOrCreate(
                    ['dataset_version_id' => $dataset->id, 'system_code' => $code],
                    ['a1_a3_per_m2' => $a1a3, 'a4_per_m2' => $a4]
                );
                $imported++;
            }
        });

        $this->info("Imported/updated {$imported} rows into dataset {$dataset->version_label} (ID {$dataset->id}).");
        return self::SUCCESS;
    }
}
