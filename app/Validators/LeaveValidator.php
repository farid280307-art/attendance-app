<?php

declare(strict_types=1);

namespace App\Validators;

use DateTimeImmutable;

final class LeaveValidator
{
    private const TYPES = ['leave', 'sick', 'permission'];

    /**
     * @param array<string, mixed> $input
     * @return array{valid:bool,data:array{type:string,start_date:string,end_date:string,reason:string},errors:array<string,string>}
     */
    public function validateCreate(array $input): array
    {
        $type = $this->stringValue($input['type'] ?? null);
        $startDate = $this->stringValue($input['start_date'] ?? null);
        $endDate = $this->stringValue($input['end_date'] ?? null);
        $reason = $this->stringValue($input['reason'] ?? null);
        $errors = [];

        if ($type === '') {
            $errors['type'] = 'Jenis pengajuan wajib dipilih.';
        } elseif (!in_array($type, self::TYPES, true)) {
            $errors['type'] = 'Jenis pengajuan tidak valid.';
        }

        $validStartDate = $this->validDate($startDate);
        if ($startDate === '') {
            $errors['start_date'] = 'Tanggal mulai wajib diisi.';
        } elseif (!$validStartDate) {
            $errors['start_date'] = 'Tanggal mulai tidak valid.';
        }

        $validEndDate = $this->validDate($endDate);
        if ($endDate === '') {
            $errors['end_date'] = 'Tanggal selesai wajib diisi.';
        } elseif (!$validEndDate) {
            $errors['end_date'] = 'Tanggal selesai tidak valid.';
        } elseif ($validStartDate && $endDate < $startDate) {
            $errors['end_date'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
        }

        $reasonLength = $this->stringLength($reason);
        if ($reason === '') {
            $errors['reason'] = 'Alasan wajib diisi.';
        } elseif ($reasonLength < 5) {
            $errors['reason'] = 'Alasan minimal 5 karakter.';
        } elseif ($reasonLength > 2000) {
            $errors['reason'] = 'Alasan maksimal 2.000 karakter.';
        }

        return [
            'valid' => $errors === [],
            'data' => [
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $reason,
            ],
            'errors' => $errors,
        ];
    }

    /** @param array<string, mixed> $input @return array{valid:bool,data:array{rejection_reason:string},errors:array<string,string>} */
    public function validateRejection(array $input): array
    {
        $reason = $this->stringValue($input['rejection_reason'] ?? null);
        $length = $this->stringLength($reason);
        $errors = [];

        if ($reason === '') {
            $errors['rejection_reason'] = 'Alasan penolakan wajib diisi.';
        } elseif ($length < 5) {
            $errors['rejection_reason'] = 'Alasan penolakan minimal 5 karakter.';
        } elseif ($length > 1000) {
            $errors['rejection_reason'] = 'Alasan penolakan maksimal 1.000 karakter.';
        }

        return [
            'valid' => $errors === [],
            'data' => ['rejection_reason' => $reason],
            'errors' => $errors,
        ];
    }

    private function validDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        return $date !== false
            && $date->format('Y-m-d') === $value
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
