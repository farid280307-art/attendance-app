<?php

declare(strict_types=1);

namespace App\Validators;

use DateTimeImmutable;

final class MonthValidator
{
    /** @return array{valid:bool,value:string,year:int,month:int,error:?string} */
    public function validate(mixed $value, DateTimeImmutable $now): array
    {
        $fallback = $now->format('Y-m');

        if ($value !== null && !is_string($value)) {
            return $this->result(false, $fallback, 'Format bulan tidak valid. Bulan saat ini ditampilkan.');
        }

        $normalized = is_string($value) ? trim($value) : '';

        if ($normalized === '') {
            return $this->result(true, $fallback, null);
        }

        if (preg_match('/^(\d{4})-(\d{2})$/', $normalized, $matches) !== 1) {
            return $this->result(false, $fallback, 'Format bulan tidak valid. Bulan saat ini ditampilkan.');
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            return $this->result(false, $fallback, 'Bulan harus berada pada rentang tahun 2000–2100. Bulan saat ini ditampilkan.');
        }

        return $this->result(true, $normalized, null);
    }

    /** @return array{valid:bool,value:string,year:int,month:int,error:?string} */
    private function result(bool $valid, string $value, ?string $error): array
    {
        [$year, $month] = array_map('intval', explode('-', $value));

        return [
            'valid' => $valid,
            'value' => $value,
            'year' => $year,
            'month' => $month,
            'error' => $error,
        ];
    }
}
