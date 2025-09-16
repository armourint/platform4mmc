<?php

namespace App\Console\Commands;

use App\Imports\RowsWithHeadings;
use App\Models\City;
use App\Models\Location;
use App\Models\Prediction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;

class ImportModelResults extends Command
{
    /**
     * Example:
     *   php artisan import:model-results data/model_results.xlsx --city=Cork
     * The {path} is relative to storage/app unless absolute.
     */
    protected $signature = 'import:model-results
        {path : storage/app path or absolute}
        {--city=Cork : City name to associate the locations/predictions with}';

    protected $description = 'Import model_results.xlsx into normalized tables (cities, locations, predictions)';

    public function handle(): int
    {
        $path = $this->argument('path');
        $cityName = $this->option('city');

        $full = str_starts_with($path, '/')
            ? $path
            : storage_path('app/'.ltrim($path, '/'));

        if (!is_file($full)) {
            $this->error("File not found: {$full}");
            return self::FAILURE;
        }

        // Normalize headers like "Time" -> "time", "Latitude" -> "latitude"
        HeadingRowFormatter::default('slug');

        $city = City::firstOrCreate(['name' => $cityName]);

        // Read the first sheet with headings
        $sheets = Excel::toCollection(new RowsWithHeadings, $full);
        if ($sheets->isEmpty()) {
            $this->warn('No sheets found.');
            return self::SUCCESS;
        }
        $rows = $sheets->first(); // first worksheet

        $madeLoc = 0;
        $madePred = 0;
        $skipped  = 0;

        DB::transaction(function () use ($rows, $city, &$madeLoc, &$madePred, &$skipped) {
            // Cache locations for fewer queries
            $locCache = $city->locations()->get()->keyBy('code');

            foreach ($rows as $r) {
                // Expected keys after slugging:
                // location, i, j, time, target, latitude, longitude, rf, rbf, rnn, dt, ann, lstm, gru
                $r = is_array($r) ? $r : $r->toArray();
                $locRaw  = $r['location'] ?? null;
                $timeRaw = $r['time'] ?? null;

                if (empty($locRaw) || empty($timeRaw)) {
                    $skipped++;
                    continue;
                }

                $code = trim((string) $locRaw);

                // Create/reuse location
                if (!$locCache->has($code)) {
                    $loc = $city->locations()->create([
                        'code' => $code,
                        'i'    => isset($r['i']) ? (int)$r['i'] : null,
                        'j'    => isset($r['j']) ? (int)$r['j'] : null,
                        'lat'  => isset($r['latitude']) ? (float)$r['latitude'] : null,
                        'lng'  => isset($r['longitude']) ? (float)$r['longitude'] : null,
                    ]);
                    $locCache->put($code, $loc);
                    $madeLoc++;
                } else {
                    $loc = $locCache->get($code);
                }

                // Parse time robustly (handles Excel serials & strings)
                try {
                    if (is_numeric($timeRaw)) {
                        // Excel date serial (e.g., 45100.5)
                        $at = Carbon::instance(XlsDate::excelToDateTimeObject((float)$timeRaw));
                    } else {
                        $at = Carbon::parse((string)$timeRaw);
                    }
                } catch (\Throwable $e) {
                    $skipped++;
                    continue;
                }

                // Insert or update prediction for this (location, at)
                $prediction = Prediction::updateOrCreate(
                    ['location_id' => $loc->id, 'at' => $at],
                    [
                        'year'   => (int) $at->year,
                        'target' => self::f($r, 'target'),
                        'rf'     => self::f($r, 'rf'),
                        'rbf'    => self::f($r, 'rbf'),
                        'dt'     => self::f($r, 'dt'),
                        'ann'    => self::f($r, 'ann'),
                        'rnn'    => self::f($r, 'rnn'),
                        'lstm'   => self::f($r, 'lstm'),
                        'gru'    => self::f($r, 'gru'),
                    ]
                );

                if ($prediction->wasRecentlyCreated) {
                    $madePred++;
                }
            }
        });

        $this->info("Import complete. New locations: {$madeLoc}, new predictions: {$madePred}, skipped rows: {$skipped}");
        return self::SUCCESS;
    }

    /**
     * Cast a numeric field safely (null if empty).
     */
    private static function f(array $row, string $key): ?float
    {
        if (!array_key_exists($key, $row) || $row[$key] === '' || $row[$key] === null) {
            return null;
        }
        return is_numeric($row[$key]) ? (float)$row[$key] : (float)str_replace(',', '.', (string)$row[$key]);
    }
}
