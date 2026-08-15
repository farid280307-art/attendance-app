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

        if ($users->existsByUsername('julianti')) {
            return false;
        }

        $passwordValue = getenv('JULIANTI_SEED_PASSWORD');
        $password = $passwordValue === false || trim($passwordValue) === ''
            ? 'julianti123'
            : trim($passwordValue);

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new RuntimeException('Gagal membuat hash password akun Julianti.');
        }

        $users->create([
            'name' => 'Julianti',
            'employee_code' => 'EMP001',
            'username' => 'julianti',
            'password' => $passwordHash,
            'role' => 'employee',
            'is_active' => 1,
        ]);

        return true;
    }
}
