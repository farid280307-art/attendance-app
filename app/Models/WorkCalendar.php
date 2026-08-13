<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class WorkCalendar
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function getForUserDateRange(int $userId, string $startDate, string $endDate): array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `user_id`, `work_date`, `work_schedule_id`, `day_type`,
                    `schedule_name`, `holiday_name`, `generated_at`
             FROM `employee_work_calendars`
             WHERE `user_id` = :user_id
               AND `work_date` BETWEEN :start_date AND :end_date
             ORDER BY `work_date` ASC'
        );
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('start_date', $startDate);
        $statement->bindValue('end_date', $endDate);
        $statement->execute();

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findForUserDate(int $userId, string $date): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `user_id`, `work_date`, `work_schedule_id`, `day_type`,
                    `schedule_name`, `holiday_name`, `generated_at`
             FROM `employee_work_calendars`
             WHERE `user_id` = :user_id AND `work_date` = :work_date
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId, 'work_date' => $date]);
        $calendar = $statement->fetch();

        return is_array($calendar) ? $calendar : null;
    }

    /** @param array<string, int|string|null> $data */
    public function insertGeneratedDayIfMissing(array $data): bool
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `employee_work_calendars`
                (`user_id`, `work_date`, `work_schedule_id`, `day_type`, `schedule_name`,
                 `holiday_name`, `generated_at`)
             VALUES
                (:user_id, :work_date, :work_schedule_id, :day_type, :schedule_name,
                 :holiday_name, :generated_at)
             ON DUPLICATE KEY UPDATE `id` = `id`'
        );
        $statement->execute($data);

        return $statement->rowCount() === 1;
    }

    /** @param array<string, int|string|null> $data */
    public function upsertGeneratedDay(array $data): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `employee_work_calendars`
                (`user_id`, `work_date`, `work_schedule_id`, `day_type`, `schedule_name`,
                 `holiday_name`, `generated_at`)
             VALUES
                (:user_id, :work_date, :work_schedule_id, :day_type, :schedule_name,
                 :holiday_name, :generated_at)
             ON DUPLICATE KEY UPDATE
                `work_schedule_id` = VALUES(`work_schedule_id`),
                `day_type` = VALUES(`day_type`),
                `schedule_name` = VALUES(`schedule_name`),
                `holiday_name` = VALUES(`holiday_name`),
                `generated_at` = VALUES(`generated_at`)'
        );
        $statement->execute($data);
    }

    public function deleteFromDateForUser(int $userId, string $date): int
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM `employee_work_calendars`
             WHERE `user_id` = :user_id AND `work_date` >= :work_date'
        );
        $statement->execute(['user_id' => $userId, 'work_date' => $date]);

        return $statement->rowCount();
    }

    public function deleteFutureBySchedule(int $scheduleId, string $date): int
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM `employee_work_calendars`
             WHERE `work_schedule_id` = :work_schedule_id AND `work_date` >= :work_date'
        );
        $statement->execute(['work_schedule_id' => $scheduleId, 'work_date' => $date]);

        return $statement->rowCount();
    }

    public function deleteDateForAllUsers(string $date): int
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM `employee_work_calendars` WHERE `work_date` = :work_date'
        );
        $statement->execute(['work_date' => $date]);

        return $statement->rowCount();
    }

    /** @return array{covered_days:int,total_days:int,complete:bool} */
    public function getMonthCoverage(int $userId, string $startDate, string $endDate, int $totalDays): array
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM `employee_work_calendars`
             WHERE `user_id` = :user_id
               AND `work_date` BETWEEN :start_date AND :end_date'
        );
        $statement->execute([
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        $coveredDays = (int) $statement->fetchColumn();

        return [
            'covered_days' => $coveredDays,
            'total_days' => $totalDays,
            'complete' => $coveredDays === $totalDays,
        ];
    }
}
