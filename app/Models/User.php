<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class User
{
    public function __construct(private PDO $pdo)
    {
    }

    public function existsByUsername(string $username): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM `users` WHERE `username` = :username LIMIT 1'
        );
        $statement->execute(['username' => $username]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * @param array{
     *     name: string,
     *     employee_code: string,
     *     username: string,
     *     password: string,
     *     role: string,
     *     is_active: int
     * } $data
     */
    public function create(array $data): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `users`
                (`name`, `employee_code`, `username`, `password`, `role`, `is_active`)
             VALUES
                (:name, :employee_code, :username, :password, :role, :is_active)'
        );
        $statement->execute([
            'name' => $data['name'],
            'employee_code' => $data['employee_code'],
            'username' => $data['username'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => $data['is_active'],
        ]);
    }
}
