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

    /** @return array<int, array<string, mixed>> */
    public function getEmployeesForReport(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `name`, `employee_code`, `is_active`
             FROM `users`
             WHERE `role` = :role
             ORDER BY `is_active` DESC, `name` ASC, `id` ASC'
        );
        $statement->execute(['role' => 'employee']);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findEmployeeForReport(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `name`, `employee_code`, `is_active`
             FROM `users`
             WHERE `id` = :id AND `role` = :role
             LIMIT 1'
        );
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->bindValue('role', 'employee');
        $statement->execute();
        $employee = $statement->fetch();

        return is_array($employee) ? $employee : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getEmployees(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT u.`id`, u.`name`, u.`employee_code`, u.`username`,
                    u.`work_schedule_id`, u.`is_active`, u.`created_at`, u.`updated_at`,
                    ws.`name` AS `work_schedule_name`
             FROM `users` AS u
             LEFT JOIN `work_schedules` AS ws ON ws.`id` = u.`work_schedule_id`
             WHERE u.`role` = :role
             ORDER BY u.`is_active` DESC, u.`name` ASC, u.`id` ASC'
        );
        $statement->execute(['role' => 'employee']);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findEmployeeById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT u.`id`, u.`name`, u.`employee_code`, u.`username`,
                    u.`work_schedule_id`, u.`is_active`, u.`created_at`, u.`updated_at`,
                    ws.`name` AS `work_schedule_name`,
                    ws.`start_time` AS `work_schedule_start_time`,
                    ws.`end_time` AS `work_schedule_end_time`,
                    ws.`late_tolerance_minutes` AS `work_schedule_late_tolerance_minutes`,
                    ws.`is_active` AS `work_schedule_active`
             FROM `users` AS u
             LEFT JOIN `work_schedules` AS ws ON ws.`id` = u.`work_schedule_id`
             WHERE u.`id` = :id AND u.`role` = :role
             LIMIT 1'
        );
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->bindValue('role', 'employee');
        $statement->execute();
        $employee = $statement->fetch();

        return is_array($employee) ? $employee : null;
    }

    public function employeeCodeExists(string $employeeCode, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM `users` WHERE `employee_code` = :employee_code';

        if ($exceptId !== null) {
            $sql .= ' AND `id` <> :except_id';
        }

        $sql .= ' LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->bindValue('employee_code', $employeeCode);

        if ($exceptId !== null) {
            $statement->bindValue('except_id', $exceptId, PDO::PARAM_INT);
        }

        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM `users` WHERE `username` = :username';

        if ($exceptId !== null) {
            $sql .= ' AND `id` <> :except_id';
        }

        $sql .= ' LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->bindValue('username', $username);

        if ($exceptId !== null) {
            $statement->bindValue('except_id', $exceptId, PDO::PARAM_INT);
        }

        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    /** @param array{name:string,employee_code:string,username:string,password:string,is_active:int} $data */
    public function createEmployee(array $data): int
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
            'role' => 'employee',
            'is_active' => $data['is_active'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array{name:string,employee_code:string,username:string,is_active:int} $data */
    public function updateEmployee(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE `users`
             SET `name` = :name,
                 `employee_code` = :employee_code,
                 `username` = :username,
                 `is_active` = :is_active
             WHERE `id` = :id AND `role` = :role'
        );
        $statement->execute([
            'name' => $data['name'],
            'employee_code' => $data['employee_code'],
            'username' => $data['username'],
            'is_active' => $data['is_active'],
            'id' => $id,
            'role' => 'employee',
        ]);
    }

    public function setEmployeeActive(int $id, bool $active): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE `users`
             SET `is_active` = :is_active
             WHERE `id` = :id AND `role` = :role'
        );
        $statement->bindValue('is_active', $active ? 1 : 0, PDO::PARAM_INT);
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->bindValue('role', 'employee');
        $statement->execute();

        return $statement->rowCount() === 1;
    }

    public function updateEmployeePassword(int $id, string $passwordHash): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE `users`
             SET `password` = :password
             WHERE `id` = :id AND `role` = :role'
        );
        $statement->bindValue('password', $passwordHash);
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->bindValue('role', 'employee');
        $statement->execute();

        return $statement->rowCount() === 1;
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
