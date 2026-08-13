<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\WorkLocation;
use App\Services\AttendanceException;
use App\Services\AttendanceService;
use App\Services\LocationService;
use App\Validators\LocationValidator;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class AttendanceController
{
    public function index(): void
    {
        $user = \auth_user();

        if ($user === null) {
            \redirect('/login');
        }

        $activeLocations = (new WorkLocation(\db()))->getActive();
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
        $attendanceState = (new AttendanceService(\db()))->getTodayState((int) $user['id'], $now);

        \view('attendance.index', [
            'user' => $user,
            'activeLocationCount' => count($activeLocations),
            'attendanceState' => $attendanceState,
            'todayLabel' => \indonesian_date($now),
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

    public function submit(): void
    {
        if (\request_exceeds_post_max_size()) {
            \json_response([
                'success' => false,
                'message' => 'Ukuran request melebihi batas upload server. Ambil ulang selfie dengan ukuran lebih kecil.',
                'reset_workflow' => true,
            ], 413);
            return;
        }

        if (!\verify_csrf()) {
            \json_response([
                'success' => false,
                'message' => 'Sesi telah berakhir. Muat ulang halaman lalu coba lagi.',
            ], 419);
            return;
        }

        $user = \auth_user();

        if ($user === null) {
            \json_response([
                'success' => false,
                'message' => 'Sesi login tidak valid.',
            ], 403);
            return;
        }

        $validation = (new LocationValidator())->validate($_POST);

        if (!$validation['valid']) {
            \json_response([
                'success' => false,
                'message' => 'Data lokasi yang dikirim tidak valid.',
                'errors' => $validation['errors'],
                'reset_workflow' => true,
            ], 422);
            return;
        }

        try {
            $result = (new AttendanceService(\db()))->submit(
                $user,
                [
                    'latitude' => $validation['data']['latitude'],
                    'longitude' => $validation['data']['longitude'],
                    'accuracy' => $validation['data']['accuracy'],
                ],
                is_array($_FILES['selfie'] ?? null) ? $_FILES['selfie'] : [],
                new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')),
                [
                    'ip_address' => $this->serverValue('REMOTE_ADDR', 45),
                    'user_agent' => $this->serverValue('HTTP_USER_AGENT', 2000),
                ]
            );
            $result['redirect_url'] = \url('/attendance');
            \json_response($result);
        } catch (AttendanceException $exception) {
            $response = [
                'success' => false,
                'message' => $exception->getMessage(),
                'reset_workflow' => $exception->shouldResetWorkflow(),
            ];

            if ($exception->errors() !== []) {
                $response['errors'] = $exception->errors();
            }

            \json_response($response, $exception->httpStatus());
        } catch (Throwable $exception) {
            error_log('Attendance submit gagal: ' . $exception->getMessage());
            \json_response([
                'success' => false,
                'message' => 'Absensi tidak dapat diproses saat ini. Silakan coba lagi.',
            ], 500);
        }
    }

    private function serverValue(string $key, int $maxLength): ?string
    {
        $value = $_SERVER[$key] ?? null;

        return is_string($value) && $value !== '' ? substr($value, 0, $maxLength) : null;
    }
}
