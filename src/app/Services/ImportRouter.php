<?php

namespace App\Services;

use App\Models\DataImport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImportRouter
{
    /**
     * Dispatch the appropriate importer for a given DataImport row.
     *
     * Conventions this method follows:
     * - Always passes --import-id so the command can resolve the stored file path.
     * - If the UI/queue created a dataset_version_id already, also pass --dataset-version-id
     *   so the importer reuses it (prevents duplicate dataset_versions).
     * - Keeps config-driven options for backwards compatibility:
     *     - path_option:   still pass an absolute path if the command wants it
     *     - dataset_option:ONLY pass a *label* if provided in import->meta['dataset_version_label']
     * - Supports --reset and extra static args from config.
     *
     * @return int Rows processed (best-effort), or 0 if unknown.
     */
    public static function handle(DataImport $import): int
    {
        $map = config("mmc_imports.{$import->module}");
        if (!$map) {
            throw new RuntimeException("Unknown import module: {$import->module}");
        }

        // Resolve an absolute path for compatibility with commands that still accept --path
        $absolutePath = Storage::disk($import->disk)->path($import->path);

        // Build Artisan args from config + import meta
        $args = [];

        /**
         * Always pass --import-id so the command can:
         *  - find the uploaded file via Storage::disk($disk)->path($path)
         *  - reuse $import->dataset_version_id
         */
        $args['--import-id'] = (string) $import->id;

        /**
         * If the import created a dataset version already, pass --dataset-version-id
         * so the importer inserts into THAT dataset_version and does not create another.
         */
        if (!empty($import->dataset_version_id)) {
            $args['--dataset-version-id'] = (string) $import->dataset_version_id;
        }

        /**
         * Back-compat: if the command in config still expects a file path,
         * pass the absolute container path too.
         */
        if (!empty($map['path_option'])) {
            $args[$map['path_option']] = $absolutePath;
        }

        /**
         * Back-compat: some older commands took a dataset *label* (e.g. "--dataset-version").
         * Only pass that if you explicitly supplied one in import->meta.
         * (Do NOT pass the numeric id to this option.)
         */
        $label = data_get($import->meta, 'dataset_version_label');
        if (!empty($map['dataset_option']) && $label) {
            $args[$map['dataset_option']] = $label;
        }

        // reset flag from UI (if this module supports it)
        $reset = (bool) data_get($import->meta, 'reset', false);
        if (!empty($map['supports_reset']) && $reset) {
            $args['--reset'] = true;
        }

        // extra static args from config (key/value pairs)
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

        // Fallback: if output did not indicate rows, return 0
        return $rows ?? 0;
    }

    /**
     * Looks for "Imported N", "Processed N rows", etc.
     */
    protected static function guessRowsFromOutput(string $out): ?int
    {
        if (preg_match('/\b(\d{1,7})\b.*(rows|row|imported|processed|created)/i', $out, $m)) {
            return (int) $m[1];
        }
        return null;
    }
}
