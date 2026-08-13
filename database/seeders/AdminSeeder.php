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

        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);

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
