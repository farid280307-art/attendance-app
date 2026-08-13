<?php

declare(strict_types=1);

namespace App\Validators;

final class AuthValidator
{
    private const MAX_USERNAME_LENGTH = 50;
    private const MAX_PASSWORD_LENGTH = 4096;

    /**
     * @param array<string, mixed> $input
     * @return array{
     *     valid: bool,
     *     data: array{username: string, password: string},
     *     errors: array<string, string>
     * }
     */
    public function validateLogin(array $input): array
    {
        $errors = [];
        $rawUsername = $input['username'] ?? '';
        $rawPassword = $input['password'] ?? '';
        $username = is_string($rawUsername) ? trim($rawUsername) : '';
        $password = is_string($rawPassword) ? $rawPassword : '';

        if (!is_string($rawUsername) || $username === '') {
            $errors['username'] = 'Username wajib diisi.';
        } elseif ($this->stringLength($username) > self::MAX_USERNAME_LENGTH) {
            $errors['username'] = 'Username maksimal 50 karakter.';
        }

        if (!is_string($rawPassword) || $password === '') {
            $errors['password'] = 'Password wajib diisi.';
        } elseif (strlen($password) > self::MAX_PASSWORD_LENGTH) {
            $errors['password'] = 'Password terlalu panjang.';
        }

        return [
            'valid' => $errors === [],
            'data' => [
                'username' => $username,
                'password' => $password,
            ],
            'errors' => $errors,
        ];
    }

    private function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
