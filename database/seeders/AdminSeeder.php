<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use PDO;
use RuntimeException;

final class AdminSeeder
{
    public function run(PDO $pdo): bool
    {
        $users = new User($pdo);

        if ($users->existsByUsername('admin')) {
            return false;
        }

        $environment = (string) ($GLOBALS['config']['app']['environment'] ?? 'development');
        $passwordValue = getenv('ADMIN_SEED_PASSWORD');
        $password = $passwordValue === false ? '' : $passwordValue;

        if ($password === '') {
            if ($environment === 'production') {
                throw new RuntimeException(
                    'ADMIN_SEED_PASSWORD wajib diatur sebelum membuat admin pada environment production.'
                );
            }

            $password = 'admin123';
        }

        if ($environment === 'production' && strlen($password) < 12) {
            throw new RuntimeException('ADMIN_SEED_PASSWORD production minimal 12 karakter.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new RuntimeException('Gagal membuat hash password admin.');
        }

        $users->create([
            'name' => 'Administrator',
            'employee_code' => 'ADMIN001',
            'username' => 'admin',
            'password' => $passwordHash,
            'role' => 'admin',
            'is_active' => 1,
        ]);

        return true;
    }
}
