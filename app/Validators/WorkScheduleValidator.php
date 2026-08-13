<?php

declare(strict_types=1);

namespace App\Validators;

final class WorkScheduleValidator
{
    /**
     * @param array<string, mixed> $input
     * @return array{valid:bool, data:array<string, mixed>, errors:array<string, string>}
     */
    public function validate(array $input): array
    {
        $name = $this->scalarString($input['name'] ?? null);
        $startTime = $this->scalarString($input['start_time'] ?? null);
        $endTime = $this->scalarString($input['end_time'] ?? null);
        $tolerance = $this->scalarString($input['late_tolerance_minutes'] ?? null);
        $workingDays = $this->workingDays($input['working_days'] ?? null);
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Nama jadwal wajib diisi.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Nama jadwal maksimal 100 karakter.';
        }

        if (!$this->isValidTime($startTime)) {
            $errors['start_time'] = 'Jam masuk wajib menggunakan format HH:MM.';
        }

        if (!$this->isValidTime($endTime)) {
            $errors['end_time'] = 'Jam pulang wajib menggunakan format HH:MM.';
        } elseif (!isset($errors['start_time']) && $endTime <= $startTime) {
            $errors['end_time'] = 'Jam pulang harus setelah jam masuk.';
        }

        $normalizedTolerance = filter_var($tolerance, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 180],
        ]);
        if ($normalizedTolerance === false) {
            $errors['late_tolerance_minutes'] = 'Toleransi keterlambatan harus berupa bilangan bulat antara 0 dan 180 menit.';
        }

        if (!$workingDays['valid']) {
            $errors['working_days'] = $workingDays['error'];
        }

        return [
            'valid' => $errors === [],
            'data' => [
                'name' => $name,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'late_tolerance_minutes' => $normalizedTolerance === false ? $tolerance : (int) $normalizedTolerance,
                'is_active' => $this->isChecked($input['is_active'] ?? null) ? 1 : 0,
                'working_days' => $workingDays['days'],
            ],
            'errors' => $errors,
        ];
    }

    private function scalarString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function isValidTime(string $value): bool
    {
        return preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $value) === 1;
    }

    private function isChecked(mixed $value): bool
    {
        return in_array($value, [1, '1', true, 'on'], true);
    }

    /** @return array{valid:bool,days:array<int,int>,error:string} */
    private function workingDays(mixed $value): array
    {
        if (!is_array($value)) {
            return ['valid' => false, 'days' => [], 'error' => 'Pilih minimal satu hari kerja.'];
        }

        $days = [];

        foreach ($value as $day) {
            if (!is_int($day) && !is_string($day)) {
                return ['valid' => false, 'days' => [], 'error' => 'Pilihan hari kerja tidak valid.'];
            }

            $normalized = filter_var($day, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 7],
            ]);

            if ($normalized === false || in_array((int) $normalized, $days, true)) {
                return ['valid' => false, 'days' => [], 'error' => 'Pilihan hari kerja tidak valid atau berulang.'];
            }

            $days[] = (int) $normalized;
        }

        sort($days, SORT_NUMERIC);

        return [
            'valid' => $days !== [],
            'days' => $days,
            'error' => $days === [] ? 'Pilih minimal satu hari kerja.' : '',
        ];
    }
}
