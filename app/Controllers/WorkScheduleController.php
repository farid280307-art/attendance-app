<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use App\Services\WorkScheduleService;
use App\Validators\WorkScheduleValidator;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class WorkScheduleController
{
    public function index(): void
    {
        $pdo = \db();
        $scheduleModel = new WorkSchedule($pdo);

        $schedules = array_map(static function (array $schedule): array {
            $schedule['working_days'] = $schedule['working_days'] === null
                ? []
                : array_map('intval', explode(',', (string) $schedule['working_days']));

            return $schedule;
        }, $scheduleModel->getAll());
        $allEmployees = (new User($pdo))->getActiveEmployeesWithSchedule();
        $shiftFilter = $this->validateShiftFilter($_GET['shift'] ?? null, $schedules);
        $employees = $this->filterEmployeesByShift($allEmployees, $shiftFilter['value']);

        \view('admin.work-schedules.index', [
            'user' => \auth_user(),
            'schedules' => $schedules,
            'activeSchedules' => $scheduleModel->getActive(),
            'employees' => $employees,
            'allEmployeeCount' => count($allEmployees),
            'filteredEmployeeCount' => count($employees),
            'selectedShift' => $shiftFilter['value'],
            'selectedShiftLabel' => $shiftFilter['label'],
            'shiftFilterError' => $shiftFilter['error'],
            'success' => \flash('success'),
            'error' => \flash('error'),
        ]);
    }

    public function create(): void
    {
        $this->renderForm('admin.work-schedules.create', [
            'name' => '',
            'start_time' => '',
            'end_time' => '',
            'late_tolerance_minutes' => 0,
            'is_active' => 1,
            'working_days' => [],
        ]);
    }

    public function store(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $validation = (new WorkScheduleValidator())->validate($_POST);
        if (!$validation['valid']) {
            http_response_code(422);
            $this->renderForm('admin.work-schedules.create', $validation['data'], $validation['errors']);
            return;
        }

        try {
            $id = (new WorkScheduleService(\db()))->create($validation['data']);
        } catch (Throwable $exception) {
            error_log('Pembuatan jadwal kerja gagal: ' . $exception->getMessage());
            \flash('error', 'Jadwal kerja tidak dapat ditambahkan saat ini.');
            \redirect('/admin/work-schedules');
        }

        $this->log(
            'work_schedule.created',
            sprintf('Jadwal kerja #%d (%s) ditambahkan.', $id, $validation['data']['name'])
        );
        $this->log(
            'work_schedule.working_days_updated',
            sprintf('Hari kerja jadwal #%d (%s) dikonfigurasi.', $id, $validation['data']['name'])
        );

        \flash('success', 'Jadwal kerja berhasil ditambahkan.');
        \redirect('/admin/work-schedules');
    }

    public function edit(): void
    {
        $id = \positive_int($_GET['id'] ?? null);
        $schedule = $id === null ? null : (new WorkSchedule(\db()))->findById($id);

        if ($schedule === null) {
            \abort(404, 'errors.404');
            return;
        }

        $schedule['start_time'] = substr((string) $schedule['start_time'], 0, 5);
        $schedule['end_time'] = substr((string) $schedule['end_time'], 0, 5);
        $schedule['working_days'] = (new WorkScheduleDay(\db()))->getWeekdaysForSchedule($id);
        $this->renderForm('admin.work-schedules.edit', $schedule, [], $id);
    }

    public function update(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $id = \positive_int($_POST['id'] ?? null);
        $model = new WorkSchedule(\db());
        $schedule = $id === null ? null : $model->findById($id);

        if ($schedule === null) {
            \abort(404, 'errors.404');
            return;
        }

        $validation = (new WorkScheduleValidator())->validate($_POST);
        if (!$validation['valid']) {
            http_response_code(422);
            $this->renderForm('admin.work-schedules.edit', $validation['data'], $validation['errors'], $id);
            return;
        }

        try {
            (new WorkScheduleService(\db()))->update($id, $validation['data'], $this->today());
        } catch (Throwable $exception) {
            error_log('Pembaruan jadwal kerja gagal: ' . $exception->getMessage());
            \flash('error', 'Jadwal kerja tidak dapat diperbarui saat ini.');
            \redirect('/admin/work-schedules');
        }

        $this->log(
            'work_schedule.updated',
            sprintf('Jadwal kerja #%d (%s) diperbarui.', $id, $validation['data']['name'])
        );
        $this->log(
            'work_schedule.working_days_updated',
            sprintf('Hari kerja jadwal #%d (%s) diperbarui.', $id, $validation['data']['name'])
        );

        \flash('success', 'Jadwal kerja berhasil diperbarui.');
        \redirect('/admin/work-schedules');
    }

    public function toggleStatus(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $id = \positive_int($_POST['id'] ?? null);
        $model = new WorkSchedule(\db());
        $schedule = $id === null ? null : $model->findById($id);

        if ($schedule === null) {
            \abort(404, 'errors.404');
            return;
        }

        $isActive = (int) $schedule['is_active'] !== 1;
        try {
            (new WorkScheduleService(\db()))->setActive($id, $isActive, $this->today());
        } catch (Throwable $exception) {
            error_log('Perubahan status jadwal kerja gagal: ' . $exception->getMessage());
            \flash('error', 'Status jadwal kerja tidak dapat diperbarui saat ini.');
            \redirect('/admin/work-schedules');
        }
        $action = $isActive ? 'activated' : 'deactivated';
        $status = $isActive ? 'diaktifkan' : 'dinonaktifkan';
        $this->log(
            'work_schedule.' . $action,
            sprintf('Jadwal kerja #%d (%s) %s.', $id, $schedule['name'], $status)
        );

        \flash('success', 'Jadwal kerja berhasil ' . $status . '.');
        \redirect('/admin/work-schedules');
    }

    public function assign(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $employeeId = \positive_int($_POST['employee_id'] ?? null);
        $scheduleId = \positive_int($_POST['work_schedule_id'] ?? null);

        if ($employeeId === null || $scheduleId === null) {
            \abort(404, 'errors.404');
            return;
        }

        $pdo = \db();
        $userModel = new User($pdo);
        $employee = $userModel->findActiveEmployeeById($employeeId);
        $scheduleModel = new WorkSchedule($pdo);
        $schedule = $scheduleModel->findById($scheduleId);
        $returnShift = $this->validateShiftFilter(
            $_POST['return_shift'] ?? null,
            $scheduleModel->getAll()
        )['value'];
        $returnPath = $this->scheduleIndexPath($returnShift);

        if ($employee === null || $schedule === null) {
            \abort(404, 'errors.404');
            return;
        }

        if ((int) $schedule['is_active'] !== 1) {
            \flash('error', 'Jadwal kerja yang dipilih tidak aktif.');
            \redirect($returnPath);
        }

        if ((int) ($employee['work_schedule_id'] ?? 0) === $scheduleId) {
            \flash('success', 'Jadwal karyawan tidak berubah.');
            \redirect($returnPath);
        }

        try {
            (new WorkScheduleService($pdo))->assign($employeeId, $scheduleId, $this->today());
        } catch (Throwable $exception) {
            error_log('Penugasan jadwal kerja gagal: ' . $exception->getMessage());
            \flash('error', 'Jadwal karyawan tidak dapat diperbarui saat ini.');
            \redirect($returnPath);
        }

        $this->log(
            'work_schedule.assigned',
            sprintf(
                'Jadwal kerja #%d (%s) ditugaskan kepada karyawan #%d (%s).',
                $scheduleId,
                $schedule['name'],
                $employeeId,
                $employee['name']
            )
        );

        \flash('success', 'Jadwal diperbarui. Kalender kerja mulai hari ini perlu digenerate ulang.');
        \redirect($returnPath);
    }

    /**
     * @param array<int, array<string, mixed>> $schedules
     * @return array{value:string,label:string,error:?string}
     */
    private function validateShiftFilter(mixed $rawShift, array $schedules): array
    {
        if ($rawShift === null || $rawShift === '') {
            return ['value' => 'all', 'label' => 'Semua Shift', 'error' => null];
        }

        if (!is_string($rawShift)) {
            return $this->invalidShiftFilter();
        }

        $shift = trim($rawShift);

        if ($shift === 'all') {
            return ['value' => 'all', 'label' => 'Semua Shift', 'error' => null];
        }

        if ($shift === 'unassigned') {
            return ['value' => 'unassigned', 'label' => 'Belum Diberi Shift', 'error' => null];
        }

        $scheduleId = \positive_int($shift);

        if ($scheduleId !== null) {
            foreach ($schedules as $schedule) {
                if ((int) ($schedule['id'] ?? 0) === $scheduleId) {
                    return [
                        'value' => (string) $scheduleId,
                        'label' => (string) ($schedule['name'] ?? 'Shift'),
                        'error' => null,
                    ];
                }
            }
        }

        return $this->invalidShiftFilter();
    }

    /** @return array{value:string,label:string,error:string} */
    private function invalidShiftFilter(): array
    {
        return [
            'value' => 'all',
            'label' => 'Semua Shift',
            'error' => 'Filter shift tidak valid dan telah dikembalikan ke Semua Shift.',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $employees
     * @return array<int, array<string, mixed>>
     */
    private function filterEmployeesByShift(array $employees, string $selectedShift): array
    {
        if ($selectedShift === 'all') {
            return $employees;
        }

        if ($selectedShift === 'unassigned') {
            return array_values(array_filter(
                $employees,
                static fn (array $employee): bool => ($employee['work_schedule_id'] ?? null) === null
            ));
        }

        $scheduleId = (int) $selectedShift;

        return array_values(array_filter(
            $employees,
            static fn (array $employee): bool => (int) ($employee['work_schedule_id'] ?? 0) === $scheduleId
        ));
    }

    private function scheduleIndexPath(string $selectedShift): string
    {
        return '/admin/work-schedules?shift=' . rawurlencode($selectedShift);
    }

    /** @param array<string, mixed> $formData @param array<string, string> $errors */
    private function renderForm(string $viewName, array $formData, array $errors = [], ?int $id = null): void
    {
        \view($viewName, [
            'user' => \auth_user(),
            'formData' => $formData,
            'errors' => $errors,
            'scheduleId' => $id,
        ]);
    }

    private function csrfIsValid(): bool
    {
        if (\verify_csrf()) {
            return true;
        }

        \abort(419, 'errors.419');
        return false;
    }

    private function log(string $action, string $description): void
    {
        try {
            (new ActivityLog(\db()))->create(
                \auth_id(),
                $action,
                $description,
                $this->serverValue('REMOTE_ADDR', 45),
                $this->serverValue('HTTP_USER_AGENT', 2000)
            );
        } catch (Throwable $exception) {
            error_log('Activity log gagal disimpan: ' . $exception->getMessage());
        }
    }

    private function serverValue(string $key, int $maxLength): ?string
    {
        $value = $_SERVER[$key] ?? null;

        return is_string($value) && $value !== '' ? substr($value, 0, $maxLength) : null;
    }

    private function today(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
    }
}
