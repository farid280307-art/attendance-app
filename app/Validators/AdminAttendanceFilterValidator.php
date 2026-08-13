<?php

declare(strict_types=1);

namespace App\Validators;

use DateTimeImmutable;

final class AdminAttendanceFilterValidator
{
    /** @param array<string,mixed> $input @return array{filters:array{date:?string,employee_id:?int,status:?string},page:int,errors:array<string,string>} */
    public function validate(array $input): array
    {
        $errors = [];
        $date = is_string($input['date'] ?? null) ? trim($input['date']) : '';
        $employee = $input['employee_id'] ?? null;
        $status = is_string($input['status'] ?? null) ? trim($input['status']) : '';
        $pageValue = $input['page'] ?? null;

        if ($date !== '' && !$this->validDate($date)) {
            $errors['date'] = 'Tanggal filter tidak valid dan diabaikan.';
            $date = '';
        }

        $employeeId = $employee === null || $employee === '' ? null : \positive_int($employee);

        if ($employee !== null && $employee !== '' && $employeeId === null) {
            $errors['employee_id'] = 'Karyawan filter tidak valid dan diabaikan.';
        }

        if ($status !== '' && !in_array($status, ['present', 'late'], true)) {
            $errors['status'] = 'Status filter tidak valid dan diabaikan.';
            $status = '';
        }

        $page = $pageValue === null || $pageValue === '' ? 1 : \positive_int($pageValue);

        if ($page === null) {
            $errors['page'] = 'Nomor halaman tidak valid. Halaman pertama ditampilkan.';
            $page = 1;
        }

        return [
            'filters' => [
                'date' => $date === '' ? null : $date,
                'employee_id' => $employeeId,
                'status' => $status === '' ? null : $status,
            ],
            'page' => $page,
            'errors' => $errors,
        ];
    }

    private function validDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) !== 1) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
