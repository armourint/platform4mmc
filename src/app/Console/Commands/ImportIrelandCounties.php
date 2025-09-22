<?php

namespace App\Console\Commands;

use App\Models\County;
use Illuminate\Console\Command;

class ImportIrelandCounties extends Command
{
    protected $signature = 'mmc:import-counties
        {--path=/mnt/data/ireland_counties.json : Path to the GeoJSON file}';

    protected $description = 'Import Ireland counties (GeoJSON FeatureCollection) into the counties table';

    public function handle(): int
    {
        $path = $this->option('path');
        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $json = json_decode(file_get_contents($path), true);
        if (!is_array($json) || ($json['type'] ?? null) !== 'FeatureCollection') {
            $this->error('Invalid GeoJSON: expected a FeatureCollection.');
            return self::FAILURE;
        }

        $features = $json['features'] ?? [];
        $upserts = 0;

        foreach ($features as $f) {
            if (($f['type'] ?? '') !== 'Feature') continue;

            $props = $f['properties'] ?? [];
            $geom  = $f['geometry'] ?? null;

            $code = trim((string)($props['id'] ?? ''));
            $name = trim((string)($props['name'] ?? ''));
            $source = isset($props['source']) ? (string)$props['source'] : null;

            if ($code === '' || $name === '') continue; // skip incomplete

            // Optional very rough centroid (first coordinate of first ring if present)
            [$lat, $lng] = $this->roughCentroid($geom);

            County::updateOrCreate(
                ['code' => $code],
                [
                    'name'         => $name,
                    'source'       => $source,
                    'geometry'     => $geom, // stored as JSON
                    'centroid_lat' => $lat,
                    'centroid_lng' => $lng,
                ]
            );
            $upserts++;
        }

        $this->info("Imported/updated {$upserts} counties.");
        return self::SUCCESS;
    }

    /**
     * Get a simple centroid guess from GeoJSON geometry (MultiPolygon/Polygon).
     * We avoid heavy math; just grab the first coordinate if present.
     */
    private function roughCentroid(?array $geometry): array
    {
        if (!$geometry || !isset($geometry['type'], $geometry['coordinates'])) {
            return [null, null];
        }

        $coords = $geometry['coordinates'];
        // GeoJSON order is [lng, lat]
        $pick = function($pt){ return is_array($pt) && count($pt) >= 2 ? [(float)$pt[1], (float)$pt[0]] : [null, null]; };

        if ($geometry['type'] === 'MultiPolygon') {
            // [ [ [ [lng,lat], ... ] ] ]
            if (isset($coords[0][0][0])) return $pick($coords[0][0][0]);
        } elseif ($geometry['type'] === 'Polygon') {
            // [ [ [lng,lat], ... ] ]
            if (isset($coords[0][0])) return $pick($coords[0][0]);
        }

        return [null, null];
    }
}
