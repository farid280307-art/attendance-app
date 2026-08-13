<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Services\ImageService;
use App\Validators\AdminAttendanceFilterValidator;

final class AdminAttendanceController
{
    private const PER_PAGE = 25;
    private const PHOTO_COLUMNS = [
        'check_in' => 'check_in_photo',
        'check_out' => 'check_out_photo',
    ];

    public function index(): void
    {
        $pdo = \db();
        $userModel = new User($pdo);
        $validation = (new AdminAttendanceFilterValidator())->validate($_GET);
        $filters = $validation['filters'];
        $errors = $validation['errors'];

        if ($filters['employee_id'] !== null && $userModel->findEmployeeForReport($filters['employee_id']) === null) {
            $errors['employee_id'] = 'Karyawan filter tidak ditemukan dan diabaikan.';
            $filters['employee_id'] = null;
        }

        $attendanceModel = new Attendance($pdo);
        $totalRows = $attendanceModel->countForAdmin($filters);
        $totalPages = max(1, (int) ceil($totalRows / self::PER_PAGE));
        $page = min($validation['page'], $totalPages);
        $offset = ($page - 1) * self::PER_PAGE;

        \view('admin.attendances.index', [
            'user' => \auth_user(),
            'attendances' => $attendanceModel->getForAdmin($filters, self::PER_PAGE, $offset),
            'employees' => $userModel->getEmployeesForReport(),
            'filters' => $filters,
            'filterErrors' => $errors,
            'pagination' => [
                'page' => $page,
                'per_page' => self::PER_PAGE,
                'total_rows' => $totalRows,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    public function show(): void
    {
        $attendance = $this->findAttendance($_GET['id'] ?? null);

        if ($attendance === null) {
            return;
        }

        $images = new ImageService();
        $attendance['has_check_in_photo'] = is_string($attendance['check_in_photo'])
            && $images->resolveAttendanceSelfie($attendance['check_in_photo']) !== null;
        $attendance['has_check_out_photo'] = is_string($attendance['check_out_photo'])
            && $images->resolveAttendanceSelfie($attendance['check_out_photo']) !== null;
        unset($attendance['check_in_photo'], $attendance['check_out_photo']);

        \view('admin.attendances.show', [
            'user' => \auth_user(),
            'attendance' => $attendance,
        ]);
    }

    public function photo(): void
    {
        $type = is_string($_GET['type'] ?? null) ? $_GET['type'] : '';

        if (!array_key_exists($type, self::PHOTO_COLUMNS)) {
            \abort(404, 'errors.404');
            return;
        }

        $attendance = $this->findAttendance($_GET['id'] ?? null);

        if ($attendance === null) {
            return;
        }

        $pathValue = $attendance[self::PHOTO_COLUMNS[$type]] ?? null;

        if (!is_string($pathValue) || $pathValue === '') {
            \abort(404, 'errors.404');
            return;
        }

        $file = (new ImageService())->resolveAttendanceSelfie($pathValue);

        if ($file === null || headers_sent()) {
            \abort(404, 'errors.404');
            return;
        }

        header('Content-Type: image/jpeg');
        header('Content-Length: ' . $file['size']);
        header('Content-Disposition: inline; filename="selfie-absensi-' . (int) $attendance['id'] . '-' . $type . '.jpg"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header("Content-Security-Policy: default-src 'none'; img-src 'self'");
        readfile($file['path']);
        exit;
    }

    /** @return array<string,mixed>|null */
    private function findAttendance(mixed $rawId): ?array
    {
        $id = \positive_int($rawId);
        $attendance = $id === null ? null : (new Attendance(\db()))->findAdminDetailById($id);

        if ($attendance === null) {
            \abort(404, 'errors.404');
            return null;
        }

        return $attendance;
    }
}
