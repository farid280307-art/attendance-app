<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\WorkLocation;
use App\Services\LocationService;
use App\Validators\LocationValidator;

final class AttendanceController
{
    public function index(): void
    {
        $user = \auth_user();

        if ($user === null) {
            \redirect('/login');
        }

        $activeLocations = (new WorkLocation(\db()))->getActive();

        \view('attendance.index', [
            'user' => $user,
            'activeLocationCount' => count($activeLocations),
        ]);
    }

    public function checkLocation(): void
    {
        if (!\verify_csrf()) {
            \json_response([
                'success' => false,
                'message' => 'Sesi telah berakhir. Muat ulang halaman lalu coba lagi.',
            ], 419);
            return;
        }

        $validation = (new LocationValidator())->validate($_POST);

        if (!$validation['valid']) {
            \json_response([
                'success' => false,
                'message' => 'Data lokasi yang dikirim tidak valid.',
                'errors' => $validation['errors'],
            ], 422);
            return;
        }

        $activeLocations = (new WorkLocation(\db()))->getActive();

        if ($activeLocations === []) {
            \json_response([
                'success' => false,
                'location_available' => false,
                'message' => 'Tidak ada lokasi absensi aktif. Hubungi administrator.',
            ]);
            return;
        }

        $latitude = $validation['data']['latitude'];
        $longitude = $validation['data']['longitude'];
        $accuracy = $validation['data']['accuracy'];
        $maximumAccuracy = (float) ($GLOBALS['config']['app']['max_location_accuracy_meters'] ?? 100.0);

        if ($accuracy > $maximumAccuracy) {
            \json_response([
                'success' => false,
                'location_available' => true,
                'location_reliable' => false,
                'within_radius' => false,
                'nearest_location' => null,
                'accuracy_meters' => round($accuracy, 1),
                'message' => 'Akurasi lokasi terlalu rendah. Pastikan GPS aktif dan coba lagi di area terbuka.',
            ]);
            return;
        }

        $nearest = (new LocationService())->findNearestLocation(
            $latitude,
            $longitude,
            $activeLocations
        );

        if ($nearest === null) {
            \json_response([
                'success' => false,
                'location_available' => false,
                'message' => 'Tidak ada lokasi absensi aktif. Hubungi administrator.',
            ]);
            return;
        }

        $withinRadius = $nearest['within_radius'];

        \json_response([
            'success' => true,
            'location_available' => true,
            'location_reliable' => true,
            'within_radius' => $withinRadius,
            'nearest_location' => [
                'id' => $nearest['id'],
                'name' => $nearest['name'],
                'distance_meters' => round($nearest['distance_meters'], 1),
                'radius_meters' => $nearest['radius_meters'],
            ],
            'accuracy_meters' => round($accuracy, 1),
            'message' => $withinRadius
                ? 'Anda berada di dalam area absensi.'
                : 'Anda harus berada di dalam area lokasi kerja untuk melakukan absensi.',
        ]);
    }
}
