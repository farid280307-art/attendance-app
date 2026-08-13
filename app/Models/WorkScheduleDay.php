<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class WorkScheduleDay
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, int> */
    public function getWeekdaysForSchedule(int $scheduleId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT `weekday`
             FROM `work_schedule_days`
             WHERE `work_schedule_id` = :work_schedule_id
             ORDER BY `weekday` ASC'
        );
        $statement->bindValue('work_schedule_id', $scheduleId, PDO::PARAM_INT);
        $statement->execute();

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @param array<int, int> $weekdays */
    public function replaceForSchedule(int $scheduleId, array $weekdays): void
    {
        $delete = $this->pdo->prepare(
            'DELETE FROM `work_schedule_days` WHERE `work_schedule_id` = :work_schedule_id'
        );
        $delete->bindValue('work_schedule_id', $scheduleId, PDO::PARAM_INT);
        $delete->execute();

        if ($weekdays === []) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO `work_schedule_days` (`work_schedule_id`, `weekday`)
             VALUES (:work_schedule_id, :weekday)'
        );

        foreach ($weekdays as $weekday) {
            $insert->bindValue('work_schedule_id', $scheduleId, PDO::PARAM_INT);
            $insert->bindValue('weekday', $weekday, PDO::PARAM_INT);
            $insert->execute();
        }
    }

    public function hasConfiguredDays(int $scheduleId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1
             FROM `work_schedule_days`
             WHERE `work_schedule_id` = :work_schedule_id
             LIMIT 1'
        );
        $statement->bindValue('work_schedule_id', $scheduleId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchColumn() !== false;
    }
}
