<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use DateTimeImmutable;
use PDO;
use PDOException;
use Throwable;

final class AttendanceService
{
    private Attendance $attendances;
    private WorkLocation $locations;
    private WorkSchedule $schedules;

    public function __construct(
        private PDO $pdo,
        private ?LocationService $locationService = null,
        private ?ImageService $imageService = null
    ) {
        $this->attendances = new Attendance($pdo);
        $this->locations = new WorkLocation($pdo);
        $this->schedules = new WorkSchedule($pdo);
        $this->locationService ??= new LocationService();
        $this->imageService ??= new ImageService();
    }

    /**
     * @return array{state:string, next_action:?string, attendance:array<string,mixed>|null}
     */
    public function getTodayState(int $userId, DateTimeImmutable $now): array
    {
        $attendance = $this->attendances->findForUserByDate($userId, $now->format('Y-m-d'));

        if ($attendance === null || $attendance['check_in'] === null) {
            return [
                'state' => 'not_checked_in',
                'next_action' => 'check_in',
                'attendance' => $attendance,
            ];
        }

        if ($attendance['check_out'] === null) {
            return [
                'state' => 'checked_in',
                'next_action' => 'check_out',
                'attendance' => $attendance,
            ];
        }

        return [
            'state' => 'completed',
            'next_action' => null,
            'attendance' => $attendance,
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @param array{latitude:float, longitude:float, accuracy:float} $coordinates
     * @param array<string, mixed> $selfie
     * @param array{ip_address?:?string, user_agent?:?string} $requestContext
     * @return array<string, mixed>
     */
    public function submit(
        array $user,
        array $coordinates,
        array $selfie,
        DateTimeImmutable $now,
        array $requestContext = []
    ): array {
        $userId = (int) ($user['id'] ?? 0);
        $scheduleId = isset($user['work_schedule_id']) ? (int) $user['work_schedule_id'] : 0;

        if ($scheduleId < 1) {
            throw new AttendanceException('Jadwal kerja belum ditentukan. Hubungi administrator.');
        }

        $schedule = $this->schedules->findById($scheduleId);

        if ($schedule === null || (int) $schedule['is_active'] !== 1) {
            throw new AttendanceException('Jadwal kerja Anda tidak aktif. Hubungi administrator.');
        }

        $date = $now->format('Y-m-d');
        $initialState = $this->getTodayState($userId, $now);

        if ($initialState['state'] === 'completed') {
            throw new AttendanceException('Absensi hari ini sudah selesai.', 409);
        }

        $nearest = $this->verifyLocation($coordinates);
        $photoPath = null;

        try {
            $photoPath = $this->imageService->storeAttendanceSelfie($selfie, $now);
            $this->pdo->beginTransaction();
            $lockedAttendance = $this->attendances->findForUserByDateForUpdate($userId, $date);
            $action = (string) $initialState['next_action'];

            if ($action === 'check_in') {
                $initialAttendance = $initialState['attendance'];
                $expectsNewRow = $initialAttendance === null;

                if (
                    ($expectsNewRow && $lockedAttendance !== null)
                    || (!$expectsNewRow && (
                        $lockedAttendance === null
                        || (int) $lockedAttendance['id'] !== (int) $initialAttendance['id']
                        || $lockedAttendance['check_in'] !== null
                    ))
                ) {
                    throw new AttendanceException('Status absensi berubah. Muat ulang halaman dan coba lagi.', 409, true);
                }

                $late = $this->calculateCheckInStatus($now, $schedule);
                $checkInData = [
                    'work_location_id' => $nearest['id'],
                    'check_in' => $now->format('Y-m-d H:i:s'),
                    'check_in_photo' => $photoPath,
                    'check_in_latitude' => $coordinates['latitude'],
                    'check_in_longitude' => $coordinates['longitude'],
                    'check_in_accuracy' => $coordinates['accuracy'],
                    'check_in_distance' => round($nearest['distance_meters'], 2),
                    'status' => $late['status'],
                    'late_minutes' => $late['late_minutes'],
                ];

                if ($expectsNewRow) {
                    $this->attendances->createCheckIn($checkInData + [
                        'user_id' => $userId,
                        'attendance_date' => $date,
                    ]);
                } elseif (!$this->attendances->fillMissingCheckIn(
                    (int) $lockedAttendance['id'],
                    $userId,
                    $checkInData
                )) {
                    throw new AttendanceException('Absen masuk sudah diproses sebelumnya.', 409, true);
                }

                $result = [
                    'success' => true,
                    'action' => 'check_in',
                    'message' => 'Absen masuk berhasil.',
                    'attendance' => [
                        'check_in' => $now->format('H:i'),
                        'status' => $late['status'],
                        'late_minutes' => $late['late_minutes'],
                    ],
                ];
            } else {
                $initialAttendance = $initialState['attendance'];

                if (
                    $lockedAttendance === null
                    || $initialAttendance === null
                    || (int) $lockedAttendance['id'] !== (int) $initialAttendance['id']
                    || $lockedAttendance['check_in'] === null
                    || $lockedAttendance['check_out'] !== null
                ) {
                    throw new AttendanceException('Absen pulang sudah diproses atau status absensi berubah.', 409, true);
                }

                $updated = $this->attendances->completeCheckOut(
                    (int) $lockedAttendance['id'],
                    $userId,
                    [
                        'check_out' => $now->format('Y-m-d H:i:s'),
                        'check_out_photo' => $photoPath,
                        'check_out_latitude' => $coordinates['latitude'],
                        'check_out_longitude' => $coordinates['longitude'],
                        'check_out_accuracy' => $coordinates['accuracy'],
                        'check_out_distance' => round($nearest['distance_meters'], 2),
                    ]
                );

                if (!$updated) {
                    throw new AttendanceException('Absen pulang sudah diproses sebelumnya.', 409, true);
                }

                $result = [
                    'success' => true,
                    'action' => 'check_out',
                    'message' => 'Absen pulang berhasil.',
                    'attendance' => [
                        'check_in' => substr((string) $lockedAttendance['check_in'], 11, 5),
                        'check_out' => $now->format('H:i'),
                        'status' => (string) $lockedAttendance['status'],
                        'late_minutes' => (int) $lockedAttendance['late_minutes'],
                    ],
                ];
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($photoPath !== null) {
                $this->imageService->delete($photoPath);
            }

            if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                throw new AttendanceException('Absensi sudah diproses sebelumnya.', 409, true);
            }

            throw $exception;
        }

        $this->logActivity(
            $userId,
            'attendance.' . $result['action'],
            $result['action'] === 'check_in' ? 'Karyawan melakukan absen masuk.' : 'Karyawan melakukan absen pulang.',
            $requestContext
        );

        return $result;
    }

    /** @return array{status:string, late_minutes:int, actual_late_minutes:int} */
    public function calculateCheckInStatus(DateTimeImmutable $checkIn, array $schedule): array
    {
        $scheduledStart = new DateTimeImmutable(
            $checkIn->format('Y-m-d') . ' ' . (string) $schedule['start_time'],
            $checkIn->getTimezone()
        );
        $lateSeconds = max(0, $checkIn->getTimestamp() - $scheduledStart->getTimestamp());
        $actualLateMinutes = (int) floor($lateSeconds / 60);
        $tolerance = (int) $schedule['late_tolerance_minutes'];
        $isLate = $actualLateMinutes > $tolerance;

        return [
            'status' => $isLate ? 'late' : 'present',
            'late_minutes' => $isLate ? $actualLateMinutes : 0,
            'actual_late_minutes' => $actualLateMinutes,
        ];
    }

    /**
     * @param array{latitude:float, longitude:float, accuracy:float} $coordinates
     * @return array{id:int,name:string,distance_meters:float,radius_meters:int,within_radius:bool}
     */
    private function verifyLocation(array $coordinates): array
    {
        $maximumAccuracy = (float) ($GLOBALS['config']['app']['max_location_accuracy_meters'] ?? 100.0);

        if ($coordinates['accuracy'] > $maximumAccuracy) {
            throw new AttendanceException(
                'Akurasi lokasi terlalu rendah. Pastikan GPS aktif dan coba lagi di area terbuka.',
                422,
                true
            );
        }

        $activeLocations = $this->locations->getActive();

        if ($activeLocations === []) {
            throw new AttendanceException('Tidak ada lokasi absensi aktif. Hubungi administrator.', 422, true);
        }

        $nearest = $this->locationService->findNearestLocation(
            $coordinates['latitude'],
            $coordinates['longitude'],
            $activeLocations
        );

        if ($nearest === null) {
            throw new AttendanceException('Tidak ada lokasi absensi aktif. Hubungi administrator.', 422, true);
        }

        if (!$nearest['within_radius']) {
            throw new AttendanceException(
                'Anda harus berada di dalam area lokasi kerja untuk melakukan absensi.',
                422,
                true
            );
        }

        return $nearest;
    }

    /** @param array{ip_address?:?string, user_agent?:?string} $context */
    private function logActivity(int $userId, string $action, string $description, array $context): void
    {
        try {
            (new ActivityLog($this->pdo))->create(
                $userId,
                $action,
                $description,
                $context['ip_address'] ?? null,
                $context['user_agent'] ?? null
            );
        } catch (Throwable $exception) {
            error_log('Activity log attendance gagal disimpan: ' . $exception->getMessage());
        }
    }
}
