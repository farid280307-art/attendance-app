<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class WorkSchedule
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function getAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT ws.`id`, ws.`name`, ws.`start_time`, ws.`end_time`,
                    ws.`late_tolerance_minutes`, ws.`is_active`, ws.`created_at`, ws.`updated_at`,
                    GROUP_CONCAT(wsd.`weekday` ORDER BY wsd.`weekday` ASC SEPARATOR ",") AS `working_days`
             FROM `work_schedules` AS ws
             LEFT JOIN `work_schedule_days` AS wsd ON wsd.`work_schedule_id` = ws.`id`
             GROUP BY ws.`id`, ws.`name`, ws.`start_time`, ws.`end_time`,
                      ws.`late_tolerance_minutes`, ws.`is_active`, ws.`created_at`, ws.`updated_at`
             ORDER BY ws.`is_active` DESC, ws.`name` ASC, ws.`id` ASC'
        );

        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function getActive(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `name`, `start_time`, `end_time`, `late_tolerance_minutes`, `is_active`
             FROM `work_schedules`
             WHERE `is_active` = :is_active
             ORDER BY `name` ASC, `id` ASC'
        );
        $statement->execute(['is_active' => 1]);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `name`, `start_time`, `end_time`, `late_tolerance_minutes`,
                    `is_active`, `created_at`, `updated_at`
             FROM `work_schedules`
             WHERE `id` = :id
             LIMIT 1'
        );
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->execute();
        $schedule = $statement->fetch();

        return is_array($schedule) ? $schedule : null;
    }

    /** @param array{name:string, start_time:string, end_time:string, late_tolerance_minutes:int, is_active:int} $data */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `work_schedules`
                (`name`, `start_time`, `end_time`, `late_tolerance_minutes`, `is_active`)
             VALUES
                (:name, :start_time, :end_time, :late_tolerance_minutes, :is_active)'
        );
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array{name:string, start_time:string, end_time:string, late_tolerance_minutes:int, is_active:int} $data */
    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE `work_schedules`
             SET `name` = :name,
                 `start_time` = :start_time,
                 `end_time` = :end_time,
                 `late_tolerance_minutes` = :late_tolerance_minutes,
                 `is_active` = :is_active
             WHERE `id` = :id'
        );
        $statement->execute($data + ['id' => $id]);
    }

    public function setActive(int $id, bool $isActive): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE `work_schedules` SET `is_active` = :is_active WHERE `id` = :id'
        );
        $statement->bindValue('is_active', $isActive ? 1 : 0, PDO::PARAM_INT);
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->execute();
    }
}
