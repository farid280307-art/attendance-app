<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class EmployeeService
{
    private User $users;

    public function __construct(private PDO $pdo)
    {
        $this->users = new User($pdo);
    }

    /**
     * @param array{name:string,employee_code:string,username:string,password:string,is_active:int} $data
     * @param array{ip_address?:?string,user_agent?:?string} $context
     */
    public function create(array $data, array $context = []): int
    {
        $this->ensureUnique($data['employee_code'], $data['username']);
        $passwordHash = $this->hashPassword($data['password']);

        try {
            $employeeId = $this->users->createEmployee([
                'name' => $data['name'],
                'employee_code' => $data['employee_code'],
                'username' => $data['username'],
                'password' => $passwordHash,
                'is_active' => $data['is_active'],
            ]);
        } catch (PDOException $exception) {
            throw $this->duplicateException($exception);
        }

        $this->log(
            $context,
            'employee.created',
            sprintf('Karyawan #%d (%s) ditambahkan.', $employeeId, $data['employee_code'])
        );

        return $employeeId;
    }

    /**
     * @param array{name:string,employee_code:string,username:string,is_active:int} $data
     * @param array{ip_address?:?string,user_agent?:?string} $context
     */
    public function update(int $employeeId, array $data, array $context = []): void
    {
        $this->ensureUnique($data['employee_code'], $data['username'], $employeeId);

        try {
            $this->users->updateEmployee($employeeId, $data);
        } catch (PDOException $exception) {
            throw $this->duplicateException($exception);
        }

        $this->log(
            $context,
            'employee.updated',
            sprintf('Data karyawan #%d (%s) diperbarui.', $employeeId, $data['employee_code'])
        );
    }

    /** @param array{ip_address?:?string,user_agent?:?string} $context */
    public function setActive(
        int $employeeId,
        string $employeeCode,
        bool $active,
        array $context = []
    ): void {
        if (!$this->users->setEmployeeActive($employeeId, $active)) {
            throw new RuntimeException('Status karyawan tidak dapat diperbarui.');
        }

        $this->log(
            $context,
            $active ? 'employee.activated' : 'employee.deactivated',
            sprintf(
                'Akun karyawan #%d (%s) %s.',
                $employeeId,
                $employeeCode,
                $active ? 'diaktifkan' : 'dinonaktifkan'
            )
        );
    }

    /** @param array{ip_address?:?string,user_agent?:?string} $context */
    public function resetPassword(
        int $employeeId,
        string $employeeCode,
        string $password,
        array $context = []
    ): void {
        if (!$this->users->updateEmployeePassword($employeeId, $this->hashPassword($password))) {
            throw new RuntimeException('Password karyawan tidak dapat diperbarui.');
        }

        $this->log(
            $context,
            'employee.password_reset',
            sprintf('Password karyawan #%d (%s) diperbarui.', $employeeId, $employeeCode)
        );
    }

    private function ensureUnique(string $employeeCode, string $username, ?int $exceptId = null): void
    {
        $errors = [];

        if ($this->users->employeeCodeExists($employeeCode, $exceptId)) {
            $errors['employee_code'] = 'Kode karyawan sudah digunakan.';
        }

        if ($this->users->usernameExists($username, $exceptId)) {
            $errors['username'] = 'Username sudah digunakan.';
        }

        if ($errors !== []) {
            throw new EmployeeException('Data karyawan belum dapat disimpan.', $errors);
        }
    }

    private function hashPassword(string $password): string
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($hash === false) {
            throw new RuntimeException('Password tidak dapat diamankan.');
        }

        return $hash;
    }

    private function duplicateException(PDOException $exception): EmployeeException
    {
        if ((string) $exception->getCode() !== '23000') {
            throw $exception;
        }

        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'employee_code')) {
            return new EmployeeException('Data karyawan belum dapat disimpan.', [
                'employee_code' => 'Kode karyawan sudah digunakan.',
            ]);
        }

        if (str_contains($message, 'username')) {
            return new EmployeeException('Data karyawan belum dapat disimpan.', [
                'username' => 'Username sudah digunakan.',
            ]);
        }

        return new EmployeeException(
            'Kode karyawan atau username sudah digunakan.',
            ['form' => 'Gunakan kode karyawan dan username lain.']
        );
    }

    /** @param array{ip_address?:?string,user_agent?:?string} $context */
    private function log(array $context, string $action, string $description): void
    {
        try {
            (new ActivityLog($this->pdo))->create(
                \auth_id(),
                $action,
                $description,
                $context['ip_address'] ?? null,
                $context['user_agent'] ?? null
            );
        } catch (Throwable $exception) {
            error_log('Activity log manajemen karyawan gagal disimpan: ' . $exception->getMessage());
        }
    }
}
