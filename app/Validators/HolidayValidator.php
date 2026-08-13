<?php

declare(strict_types=1);

namespace App\Validators;

use DateTimeImmutable;

final class HolidayValidator
{
    /** @param array<string, mixed> $input @return array{valid:bool,data:array<string,mixed>,errors:array<string,string>} */
    public function validate(array $input): array
    {
        $date = is_string($input['holiday_date'] ?? null) ? trim($input['holiday_date']) : '';
        $name = is_string($input['name'] ?? null) ? trim($input['name']) : '';
        $errors = [];

        if (!$this->validDate($date)) {
            $errors['holiday_date'] = 'Tanggal hari libur wajib menggunakan format YYYY-MM-DD yang valid.';
        }

        $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);

        if ($name === '') {
            $errors['name'] = 'Nama hari libur wajib diisi.';
        } elseif ($nameLength < 3) {
            $errors['name'] = 'Nama hari libur minimal 3 karakter.';
        } elseif ($nameLength > 150) {
            $errors['name'] = 'Nama hari libur maksimal 150 karakter.';
        }

        return [
            'valid' => $errors === [],
            'data' => [
                'holiday_date' => $date,
                'name' => $name,
                'is_active' => in_array($input['is_active'] ?? null, [1, '1', true, 'on'], true) ? 1 : 0,
            ],
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
