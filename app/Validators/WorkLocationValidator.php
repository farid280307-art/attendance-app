<?php

declare(strict_types=1);

namespace App\Validators;

final class WorkLocationValidator
{
    /**
     * @param array<string, mixed> $input
     * @return array{valid:bool, data:array<string, mixed>, errors:array<string, string>}
     */
    public function validate(array $input): array
    {
        $name = $this->scalarString($input['name'] ?? null);
        $latitude = $this->scalarString($input['latitude'] ?? null);
        $longitude = $this->scalarString($input['longitude'] ?? null);
        $radius = $this->scalarString($input['radius_meters'] ?? null);
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Nama lokasi wajib diisi.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Nama lokasi maksimal 100 karakter.';
        }

        $normalizedLatitude = filter_var($latitude, FILTER_VALIDATE_FLOAT);
        if ($latitude === '' || $normalizedLatitude === false || $normalizedLatitude < -90 || $normalizedLatitude > 90) {
            $errors['latitude'] = 'Latitude harus berupa angka antara -90 dan 90.';
        }

        $normalizedLongitude = filter_var($longitude, FILTER_VALIDATE_FLOAT);
        if ($longitude === '' || $normalizedLongitude === false || $normalizedLongitude < -180 || $normalizedLongitude > 180) {
            $errors['longitude'] = 'Longitude harus berupa angka antara -180 dan 180.';
        }

        $normalizedRadius = filter_var($radius, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 10000],
        ]);
        if ($normalizedRadius === false) {
            $errors['radius_meters'] = 'Radius harus berupa bilangan bulat antara 1 dan 10.000 meter.';
        }

        return [
            'valid' => $errors === [],
            'data' => [
                'name' => $name,
                'latitude' => $normalizedLatitude === false ? $latitude : (float) $normalizedLatitude,
                'longitude' => $normalizedLongitude === false ? $longitude : (float) $normalizedLongitude,
                'radius_meters' => $normalizedRadius === false ? $radius : (int) $normalizedRadius,
                'is_active' => $this->isChecked($input['is_active'] ?? null) ? 1 : 0,
            ],
            'errors' => $errors,
        ];
    }

    private function scalarString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function isChecked(mixed $value): bool
    {
        return in_array($value, [1, '1', true, 'on'], true);
    }
}
