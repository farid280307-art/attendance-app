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
     * Data ini khusus untuk verifikasi credential di backend dan tidak boleh
     * diteruskan langsung ke View karena menyertakan password hash.
     *
     * @return array<string, mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `name`, `employee_code`, `username`, `password`, `role`,
                    `work_schedule_id`, `is_active`
             FROM `users`
             WHERE `username` = :username
             LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `name`, `employee_code`, `username`, `role`,
                    `work_schedule_id`, `is_active`
             FROM `users`
             WHERE `id` = :id
             LIMIT 1'
        );
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->execute();
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
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
