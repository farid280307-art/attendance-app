<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Validators\WorkScheduleValidator;
use Throwable;

final class WorkScheduleController
{
    public function index(): void
    {
        $pdo = \db();
        $scheduleModel = new WorkSchedule($pdo);

        \view('admin.work-schedules.index', [
            'user' => \auth_user(),
            'schedules' => $scheduleModel->getAll(),
            'activeSchedules' => $scheduleModel->getActive(),
            'employees' => (new User($pdo))->getActiveEmployeesWithSchedule(),
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

        $model = new WorkSchedule(\db());
        $id = $model->create($validation['data']);
        $this->log(
            'work_schedule.created',
            sprintf('Jadwal kerja #%d (%s) ditambahkan.', $id, $validation['data']['name'])
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

        $model->update($id, $validation['data']);
        $this->log(
            'work_schedule.updated',
            sprintf('Jadwal kerja #%d (%s) diperbarui.', $id, $validation['data']['name'])
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
        $model->setActive($id, $isActive);
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
        $schedule = (new WorkSchedule($pdo))->findById($scheduleId);

        if ($employee === null || $schedule === null) {
            \abort(404, 'errors.404');
            return;
        }

        if ((int) $schedule['is_active'] !== 1) {
            \flash('error', 'Jadwal kerja yang dipilih tidak aktif.');
            \redirect('/admin/work-schedules');
        }

        $userModel->assignWorkSchedule($employeeId, $scheduleId);
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

        \flash('success', 'Jadwal karyawan berhasil diperbarui.');
        \redirect('/admin/work-schedules');
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
}
