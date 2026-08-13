<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\EmployeeException;
use App\Services\EmployeeService;
use App\Services\ReportService;
use App\Validators\EmployeeValidator;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class UserController
{
    public function index(): void
    {
        \view('admin.employees.index', [
            'user' => \auth_user(),
            'employees' => (new User(\db()))->getEmployees(),
            'success' => \flash('success'),
            'error' => \flash('error'),
        ]);
    }

    public function create(): void
    {
        $this->renderForm('admin.employees.create', [
            'name' => '',
            'employee_code' => '',
            'username' => '',
            'is_active' => 1,
        ]);
    }

    public function store(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $validation = (new EmployeeValidator())->validateCreate($_POST);

        if (!$validation['valid']) {
            http_response_code(422);
            $this->renderForm(
                'admin.employees.create',
                $this->identityData($validation['data']),
                $validation['errors']
            );
            return;
        }

        try {
            (new EmployeeService(\db()))->create($validation['data'], $this->requestContext());
        } catch (EmployeeException $exception) {
            http_response_code(422);
            $this->renderForm(
                'admin.employees.create',
                $this->identityData($validation['data']),
                $exception->errors(),
                null,
                $exception->getMessage()
            );
            return;
        } catch (Throwable $exception) {
            error_log('Pembuatan karyawan gagal: ' . $exception->getMessage());
            \flash('error', 'Karyawan tidak dapat ditambahkan saat ini.');
            \redirect('/admin/employees');
        }

        \flash('success', 'Karyawan berhasil ditambahkan.');
        \redirect('/admin/employees');
    }

    public function show(): void
    {
        $employee = $this->findEmployee($_GET['id'] ?? null);

        if ($employee === null) {
            return;
        }

        $this->renderDetail($employee);
    }

    public function edit(): void
    {
        $employee = $this->findEmployee($_GET['id'] ?? null);

        if ($employee === null) {
            return;
        }

        $this->renderForm('admin.employees.edit', $employee, [], (int) $employee['id']);
    }

    public function update(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $employee = $this->findEmployee($_POST['id'] ?? null);

        if ($employee === null) {
            return;
        }

        $validation = (new EmployeeValidator())->validateUpdate($_POST);

        if (!$validation['valid']) {
            http_response_code(422);
            $this->renderForm(
                'admin.employees.edit',
                $validation['data'],
                $validation['errors'],
                (int) $employee['id']
            );
            return;
        }

        try {
            (new EmployeeService(\db()))->update(
                (int) $employee['id'],
                $validation['data'],
                $this->requestContext()
            );
        } catch (EmployeeException $exception) {
            http_response_code(422);
            $this->renderForm(
                'admin.employees.edit',
                $validation['data'],
                $exception->errors(),
                (int) $employee['id'],
                $exception->getMessage()
            );
            return;
        } catch (Throwable $exception) {
            error_log('Pembaruan karyawan gagal: ' . $exception->getMessage());
            \flash('error', 'Data karyawan tidak dapat diperbarui saat ini.');
            \redirect('/admin/employees/show?id=' . $employee['id']);
        }

        \flash('success', 'Data karyawan berhasil diperbarui.');
        \redirect('/admin/employees/show?id=' . $employee['id']);
    }

    public function toggleStatus(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $employee = $this->findEmployee($_POST['id'] ?? null);

        if ($employee === null) {
            return;
        }

        $active = (int) $employee['is_active'] !== 1;

        try {
            (new EmployeeService(\db()))->setActive(
                (int) $employee['id'],
                (string) $employee['employee_code'],
                $active,
                $this->requestContext()
            );
        } catch (Throwable $exception) {
            error_log('Perubahan status karyawan gagal: ' . $exception->getMessage());
            \flash('error', 'Status akun karyawan tidak dapat diperbarui saat ini.');
            \redirect('/admin/employees');
        }

        \flash(
            'success',
            $active ? 'Akun karyawan diaktifkan.' : 'Akun karyawan dinonaktifkan.'
        );
        \redirect('/admin/employees');
    }

    public function resetPassword(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $employee = $this->findEmployee($_POST['id'] ?? null);

        if ($employee === null) {
            return;
        }

        $validation = (new EmployeeValidator())->validatePasswordReset($_POST);

        if (!$validation['valid']) {
            http_response_code(422);
            $this->renderDetail($employee, $validation['errors']);
            return;
        }

        try {
            (new EmployeeService(\db()))->resetPassword(
                (int) $employee['id'],
                (string) $employee['employee_code'],
                $validation['data']['password'],
                $this->requestContext()
            );
        } catch (Throwable $exception) {
            error_log('Reset password karyawan gagal: ' . $exception->getMessage());
            \flash('error', 'Password karyawan tidak dapat diperbarui saat ini.');
            \redirect('/admin/employees/show?id=' . $employee['id']);
        }

        \flash('success', 'Password karyawan berhasil diperbarui.');
        \redirect('/admin/employees/show?id=' . $employee['id']);
    }

    /** @return array<string, mixed>|null */
    private function findEmployee(mixed $rawId): ?array
    {
        $id = \positive_int($rawId);
        $employee = $id === null ? null : (new User(\db()))->findEmployeeById($id);

        if ($employee === null) {
            \abort(404, 'errors.404');
            return null;
        }

        return $employee;
    }

    /** @param array<string, mixed> $formData @param array<string, string> $errors */
    private function renderForm(
        string $viewName,
        array $formData,
        array $errors = [],
        ?int $employeeId = null,
        ?string $error = null
    ): void {
        \view($viewName, [
            'user' => \auth_user(),
            'formData' => $formData,
            'errors' => $errors,
            'employeeId' => $employeeId,
            'error' => $error,
        ]);
    }

    /** @param array<string, mixed> $employee @param array<string, string> $passwordErrors */
    private function renderDetail(array $employee, array $passwordErrors = []): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
        $report = (new ReportService(\db()))->getMonthlyReport(
            (int) $employee['id'],
            (int) $now->format('Y'),
            (int) $now->format('n'),
            $now
        );

        \view('admin.employees.show', [
            'user' => \auth_user(),
            'employee' => $employee,
            'monthlySummary' => $report['summary'],
            'monthLabel' => $report['period']['label'],
            'passwordErrors' => $passwordErrors,
            'success' => \flash('success'),
            'error' => \flash('error'),
        ]);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function identityData(array $data): array
    {
        return [
            'name' => $data['name'] ?? '',
            'employee_code' => $data['employee_code'] ?? '',
            'username' => $data['username'] ?? '',
            'is_active' => $data['is_active'] ?? 0,
        ];
    }

    private function csrfIsValid(): bool
    {
        if (\verify_csrf()) {
            return true;
        }

        \abort(419, 'errors.419');
        return false;
    }

    /** @return array{ip_address:?string,user_agent:?string} */
    private function requestContext(): array
    {
        return [
            'ip_address' => $this->serverValue('REMOTE_ADDR', 45),
            'user_agent' => $this->serverValue('HTTP_USER_AGENT', 2000),
        ];
    }

    private function serverValue(string $key, int $maxLength): ?string
    {
        $value = $_SERVER[$key] ?? null;

        return is_string($value) && $value !== '' ? substr($value, 0, $maxLength) : null;
    }
}
