<?php

namespace App\Services;

use App\Models\DataImport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImportRouter
{
    public static function handle(DataImport $import): int
    {
        $map = config("mmc_imports.{$import->module}");
        if (!$map) {
            throw new RuntimeException("Unknown import module: {$import->module}");
        }

        $path = Storage::disk($import->disk)->path($import->path);

        // Build Artisan args from config + import meta
        $args = [];

        // file path option
        if (!empty($map['path_option'])) {
            $args[$map['path_option']] = $path;
        }

        // dataset option (only if configured and we have an id)
        if (!empty($map['dataset_option']) && $import->dataset_version_id) {
            $args[$map['dataset_option']] = $import->dataset_version_id;
        }

        // reset flag from UI (if supported)
        $reset = (bool) data_get($import->meta, 'reset', false);
        if (!empty($map['supports_reset']) && $reset) {
            $args['--reset'] = true;
        }

        // extra static args
        foreach ((array) ($map['extra_args'] ?? []) as $k => $v) {
            $args[$k] = $v;
        }

        // optional verbosity (useful while developing)
        if (data_get($import->meta, 'verbose', false)) {
            $args['-vvv'] = true;
        }

        // Call the command
        Artisan::call($map['command'], $args);

        // Try to extract a row count from command output (optional)
        $output = trim(Artisan::output());
        $rows = self::guessRowsFromOutput($output);

        // Fallback: if output did not indicate rows, return 0 (or 1 for success)
        return $rows ?? 0;
    }

    protected static function guessRowsFromOutput(string $out): ?int
    {
        // Looks for "Processed N rows" or "Imported N" etc.
        if (preg_match('/\b(\d{1,7})\b.*(rows|imported|processed)/i', $out, $m)) {
            return (int) $m[1];
        }
        return null;
    }
}
