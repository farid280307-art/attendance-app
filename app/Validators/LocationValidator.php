<?php

declare(strict_types=1);

namespace App\Validators;

final class LocationValidator
{
    private const MAX_INPUT_ACCURACY_METERS = 10000.0;

    /**
     * @param array<string, mixed> $input
     * @return array{
     *     valid: bool,
     *     data: array{latitude?:float, longitude?:float, accuracy?:float},
     *     errors: array<string, string>
     * }
     */
    public function validate(array $input): array
    {
        $data = [];
        $errors = [];

        $latitude = $this->finiteNumber($input['latitude'] ?? null);
        if ($latitude === null || $latitude < -90.0 || $latitude > 90.0) {
            $errors['latitude'] = 'Latitude harus berupa angka antara -90 dan 90.';
        } else {
            $data['latitude'] = $latitude;
        }

        $longitude = $this->finiteNumber($input['longitude'] ?? null);
        if ($longitude === null || $longitude < -180.0 || $longitude > 180.0) {
            $errors['longitude'] = 'Longitude harus berupa angka antara -180 dan 180.';
        } else {
            $data['longitude'] = $longitude;
        }

        $accuracy = $this->finiteNumber($input['accuracy'] ?? null);
        if ($accuracy === null || $accuracy < 0.0 || $accuracy > self::MAX_INPUT_ACCURACY_METERS) {
            $errors['accuracy'] = 'Akurasi lokasi harus berupa angka antara 0 dan 10.000 meter.';
        } else {
            $data['accuracy'] = $accuracy;
        }

        return [
            'valid' => $errors === [],
            'data' => $data,
            'errors' => $errors,
        ];
    }

    private function finiteNumber(mixed $value): ?float
    {
        if (!is_int($value) && !is_float($value) && !is_string($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }
        }

        if (!is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return is_finite($number) ? $number : null;
    }
}
