<?php

declare(strict_types=1);

namespace App\Services;

final class LocationService
{
    private const EARTH_RADIUS_METERS = 6371000.0;

    public function calculateDistanceMeters(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $latitudeDelta = deg2rad($lat2 - $lat1);
        $longitudeDelta = deg2rad($lon2 - $lon1);
        $latitude1 = deg2rad($lat1);
        $latitude2 = deg2rad($lat2);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($latitude1) * cos($latitude2) * sin($longitudeDelta / 2) ** 2;
        $haversine = min(1.0, max(0.0, $haversine));
        $centralAngle = 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));

        return self::EARTH_RADIUS_METERS * $centralAngle;
    }

    /**
     * @param array<int, array<string, mixed>> $activeLocations
     * @return array{id:int, name:string, distance_meters:float, radius_meters:int, within_radius:bool}|null
     */
    public function findNearestLocation(
        float $latitude,
        float $longitude,
        array $activeLocations
    ): ?array {
        $nearest = null;

        foreach ($activeLocations as $location) {
            $distance = $this->calculateDistanceMeters(
                $latitude,
                $longitude,
                (float) $location['latitude'],
                (float) $location['longitude']
            );
            $radius = (int) $location['radius_meters'];

            if ($nearest === null || $distance < $nearest['distance_meters']) {
                $nearest = [
                    'id' => (int) $location['id'],
                    'name' => (string) $location['name'],
                    'distance_meters' => $distance,
                    'radius_meters' => $radius,
                    'within_radius' => $distance <= $radius,
                ];
            }
        }

        return $nearest;
    }
}
