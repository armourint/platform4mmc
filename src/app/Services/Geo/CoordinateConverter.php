<?php

namespace App\Services\Geo;

/**
 * Minimal EPSG:27700 (British National Grid) -> WGS84 converter.
 *
 * Strategy:
 *  - If the Proj4PHP library is available, use it (accurate).
 *  - Otherwise, use a light-weight Helmert transform + Airy 1830 ellipsoid approximation.
 *    (Good enough for plotting manufacturers on a Leaflet map.)
 */
final class CoordinateConverter
{
    
    public static function webMercatorToWgs84(float $x, float $y): array
    {
        // guard extreme values
        $max = 20037508.342789244;
        $x = max(min($x, $max), -$max);
        $y = max(min($y, $max), -$max);

        $lng = ($x / 6378137.0) * 180.0 / M_PI;
        $lat = (2.0 * atan(exp($y / 6378137.0)) - M_PI / 2.0) * 180.0 / M_PI;
        return [$lat, $lng];
    }
    
    public static function bngToWgs84(float $easting, float $northing): array
    {
        // Prefer Proj4 if available
        if (class_exists(\proj4php\Proj4php::class)) {
            $proj4 = new \proj4php\Proj4php();
            $bng   = new \proj4php\Proj('EPSG:27700', $proj4);
            $wgs84 = new \proj4php\Proj('EPSG:4326', $proj4);
            $pointSrc = new \proj4php\Point($easting, $northing);
            $pointDst = $proj4->transform($bng, $wgs84, $pointSrc);
            return [(float)$pointDst->y, (float)$pointDst->x]; // [lat, lng]
        }

        // Fallback approximate conversion (OSTN02 not applied).
        // Implementation adapted from Ordnance Survey open formulas (simplified).
        // NOTE: For production-grade accuracy, install proj4php/proj4php.
        $a = 6377563.396;  // Airy 1830 major semi-axis
        $b = 6356256.909;  // Airy 1830 minor semi-axis
        $F0 = 0.9996012717; // scale factor on central meridian
        $lat0 = deg2rad(49);
        $lon0 = deg2rad(-2);
        $N0 = -100000;
        $E0 = 400000;
        $e2 = 1 - ($b*$b)/($a*$a);
        $n = ($a - $b) / ($a + $b);

        $E = $easting; $N = $northing;

        $lat = $lat0; $M = 0;
        do {
            $lat = ($N - $N0 - $M) / ($a * $F0) + $lat;
            $Ma = (1 + $n + (5/4)*$n*$n + (5/4)*$n*$n*$n) * ($lat - $lat0);
            $Mb = (3*$n + 3*$n*$n + (21/8)*$n*$n*$n) * sin($lat - $lat0) * cos($lat + $lat0);
            $Mc = ((15/8)*$n*$n + (15/8)*$n*$n*$n) * sin(2*($lat - $lat0)) * cos(2*($lat + $lat0));
            $Md = (35/24)*$n*$n*$n * sin(3*($lat - $lat0)) * cos(3*($lat + $lat0));
            $M = $b * $F0 * ($Ma - $Mb + $Mc - $Md);
        } while (abs($N - $N0 - $M) >= 1e-5);

        $sinLat = sin($lat); $cosLat = cos($lat);
        $nu = $a * $F0 / sqrt(1 - $e2 * $sinLat * $sinLat);              // transverse radius of curvature
        $rho = $a * $F0 * (1 - $e2) / pow(1 - $e2 * $sinLat * $sinLat, 1.5); // meridional radius
        $eta2 = $nu / $rho - 1;

        $tanLat = tan($lat);
        $secLat = 1.0 / $cosLat;
        $dE = ($E - $E0);

        $VII  = $tanLat / (2 * $rho * $nu);
        $VIII = $tanLat / (24 * $rho * pow($nu,3)) * (5 + 3*$tanLat*$tanLat + $eta2 - 9*$tanLat*$tanLat*$eta2);
        $IX   = $tanLat / (720 * $rho * pow($nu,5)) * (61 + 90*$tanLat*$tanLat + 45*pow($tanLat,4));
        $X    = $secLat / $nu;
        $XI   = $secLat / (6 * pow($nu,3)) * ($nu / $rho + 2*$tanLat*$tanLat);
        $XII  = $secLat / (120 * pow($nu,5)) * (5 + 28*$tanLat*$tanLat + 24*pow($tanLat,4));
        $XIIA = $secLat / (5040 * pow($nu,7)) * (61 + 662*$tanLat*$tanLat + 1320*pow($tanLat,4) + 720*pow($tanLat,6));

        $lat = $lat - $VII* $dE*$dE + $VIII* pow($dE,4) - $IX* pow($dE,6);
        $lon = $lon0 + $X*$dE - $XI* pow($dE,3) + $XII* pow($dE,5) - $XIIA* pow($dE,7);

        // Convert OSGB36 -> WGS84 (Helmert)
        $tx = 446.448; $ty = -125.157; $tz = 542.060; // metres
        $s = 0.0000204894; // scale ppm -> unitless
        $rx = deg2rad(0.1502/3600); $ry = deg2rad(0.2470/3600); $rz = deg2rad(0.8421/3600);

        // Convert to cartesian
        $aOS = 6377563.396; $bOS = 6356256.909; $e2OS = 1 - ($bOS*$bOS)/($aOS*$aOS);
        $v = $aOS / sqrt(1 - $e2OS * sin($lat)*sin($lat));
        $x = $v * cos($lat) * cos($lon);
        $y = $v * cos($lat) * sin($lon);
        $z = ($v*(1 - $e2OS)) * sin($lat);

        // Transform
        $x2 = $tx + (1+$s)*$x + (-$rz)*$y + ($ry)*$z;
        $y2 = $ty + ($rz)*$x + (1+$s)*$y + (-$rx)*$z;
        $z2 = $tz + (-$ry)*$x + ($rx)*$y + (1+$s)*$z;

        // Back to lat/lon on WGS84
        $aW = 6378137.000; $bW = 6356752.3141; $e2W = 1 - ($bW*$bW)/($aW*$aW);
        $p = sqrt($x2*$x2 + $y2*$y2);
        $latW = atan2($z2, $p*(1-$e2W));
        $latWprev = 2;
        while (abs($latW - $latWprev) > 1e-12) {
            $vW = $aW / sqrt(1 - $e2W * sin($latW)*sin($latW));
            $latWprev = $latW;
            $latW = atan2($z2 + $e2W*$vW*sin($latW), $p);
        }
        $lonW = atan2($y2, $x2);

        return [rad2deg($latW), rad2deg($lonW)];
    }
}
