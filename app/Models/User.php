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

    public function countActiveEmployees(): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM `users`
             WHERE `role` = :role AND `is_active` = :is_active'
        );
        $statement->execute([
            'role' => 'employee',
            'is_active' => 1,
        ]);

        return (int) $statement->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public function getActiveEmployeesWithSchedule(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT u.`id`, u.`name`, u.`employee_code`, u.`work_schedule_id`,
                    ws.`name` AS `work_schedule_name`, ws.`is_active` AS `work_schedule_is_active`
             FROM `users` AS u
             LEFT JOIN `work_schedules` AS ws ON ws.`id` = u.`work_schedule_id`
             WHERE u.`role` = :role AND u.`is_active` = :is_active
             ORDER BY u.`name` ASC, u.`id` ASC'
        );
        $statement->execute([
            'role' => 'employee',
            'is_active' => 1,
        ]);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findActiveEmployeeById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `name`, `employee_code`, `work_schedule_id`
             FROM `users`
             WHERE `id` = :id AND `role` = :role AND `is_active` = :is_active
             LIMIT 1'
        );
        $statement->execute([
            'id' => $id,
            'role' => 'employee',
            'is_active' => 1,
        ]);
        $employee = $statement->fetch();

        return is_array($employee) ? $employee : null;
    }

    public function assignWorkSchedule(int $employeeId, int $scheduleId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE `users`
             SET `work_schedule_id` = :work_schedule_id
             WHERE `id` = :id AND `role` = :role AND `is_active` = :is_active'
        );
        $statement->execute([
            'work_schedule_id' => $scheduleId,
            'id' => $employeeId,
            'role' => 'employee',
            'is_active' => 1,
        ]);
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
