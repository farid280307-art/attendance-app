<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use PDO;
use RuntimeException;

final class EmployeeSeeder
{
    public function run(PDO $pdo): bool
    {
        $users = new User($pdo);

        if ($users->existsByUsername('employee')) {
            return false;
        }

        $passwordHash = password_hash('employee123', PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new RuntimeException('Gagal membuat hash password employee development.');
        }

        $users->create([
            'name' => 'Test Employee',
            'employee_code' => 'EMP001',
            'username' => 'employee',
            'password' => $passwordHash,
            'role' => 'employee',
            'is_active' => 1,
        ]);

        return true;
    }
}
