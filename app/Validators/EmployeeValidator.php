<?php

declare(strict_types=1);

namespace App\Validators;

final class EmployeeValidator
{
    /**
     * @param array<string, mixed> $input
     * @return array{valid:bool,data:array<string,mixed>,errors:array<string,string>}
     */
    public function validateCreate(array $input): array
    {
        $result = $this->validateIdentity($input);
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';
        $confirmation = is_string($input['password_confirmation'] ?? null)
            ? $input['password_confirmation']
            : '';
        $passwordLength = $this->length($password);

        if ($password === '') {
            $result['errors']['password'] = 'Password wajib diisi.';
        } elseif ($passwordLength < 8) {
            $result['errors']['password'] = 'Password minimal 8 karakter.';
        } elseif ($passwordLength > 255) {
            $result['errors']['password'] = 'Password maksimal 255 karakter.';
        }

        if ($confirmation === '') {
            $result['errors']['password_confirmation'] = 'Konfirmasi password wajib diisi.';
        } elseif (!hash_equals($password, $confirmation)) {
            $result['errors']['password_confirmation'] = 'Konfirmasi password tidak sama.';
        }

        $result['data']['password'] = $password;
        $result['valid'] = $result['errors'] === [];

        return $result;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{valid:bool,data:array<string,mixed>,errors:array<string,string>}
     */
    public function validateUpdate(array $input): array
    {
        return $this->validateIdentity($input);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{valid:bool,data:array{password:string},errors:array<string,string>}
     */
    public function validatePasswordReset(array $input): array
    {
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';
        $confirmation = is_string($input['password_confirmation'] ?? null)
            ? $input['password_confirmation']
            : '';
        $length = $this->length($password);
        $errors = [];

        if ($password === '') {
            $errors['password'] = 'Password baru wajib diisi.';
        } elseif ($length < 8) {
            $errors['password'] = 'Password baru minimal 8 karakter.';
        } elseif ($length > 255) {
            $errors['password'] = 'Password baru maksimal 255 karakter.';
        }

        if ($confirmation === '') {
            $errors['password_confirmation'] = 'Konfirmasi password baru wajib diisi.';
        } elseif (!hash_equals($password, $confirmation)) {
            $errors['password_confirmation'] = 'Konfirmasi password baru tidak sama.';
        }

        return [
            'valid' => $errors === [],
            'data' => ['password' => $password],
            'errors' => $errors,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{valid:bool,data:array<string,mixed>,errors:array<string,string>}
     */
    private function validateIdentity(array $input): array
    {
        $name = $this->stringValue($input['name'] ?? null);
        $employeeCode = $this->stringValue($input['employee_code'] ?? null);
        $username = $this->stringValue($input['username'] ?? null);
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Nama lengkap wajib diisi.';
        } elseif ($this->length($name) < 3) {
            $errors['name'] = 'Nama lengkap minimal 3 karakter.';
        } elseif ($this->length($name) > 100) {
            $errors['name'] = 'Nama lengkap maksimal 100 karakter.';
        }

        if ($employeeCode === '') {
            $errors['employee_code'] = 'Kode karyawan wajib diisi.';
        } elseif ($this->length($employeeCode) < 2) {
            $errors['employee_code'] = 'Kode karyawan minimal 2 karakter.';
        } elseif ($this->length($employeeCode) > 50) {
            $errors['employee_code'] = 'Kode karyawan maksimal 50 karakter.';
        }

        if ($username === '') {
            $errors['username'] = 'Username wajib diisi.';
        } elseif ($this->length($username) < 3) {
            $errors['username'] = 'Username minimal 3 karakter.';
        } elseif ($this->length($username) > 50) {
            $errors['username'] = 'Username maksimal 50 karakter.';
        } elseif (preg_match('/^[A-Za-z0-9._-]+$/D', $username) !== 1) {
            $errors['username'] = 'Username hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung.';
        }

        return [
            'valid' => $errors === [],
            'data' => [
                'name' => $name,
                'employee_code' => $employeeCode,
                'username' => $username,
                'is_active' => $this->checked($input['is_active'] ?? null) ? 1 : 0,
            ],
            'errors' => $errors,
        ];
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function checked(mixed $value): bool
    {
        return in_array($value, [1, '1', true, 'on'], true);
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
