<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Attendance
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array{checked_in: int, late: int}
     */
    public function getTodaySummary(string $date): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN `check_in` IS NOT NULL THEN 1 ELSE 0 END), 0) AS `checked_in`,
                COALESCE(SUM(CASE WHEN `status` = :late_status THEN 1 ELSE 0 END), 0) AS `late`
             FROM `attendances`
             WHERE `attendance_date` = :attendance_date'
        );
        $statement->execute([
            'late_status' => 'late',
            'attendance_date' => $date,
        ]);
        $summary = $statement->fetch();

        return [
            'checked_in' => (int) ($summary['checked_in'] ?? 0),
            'late' => (int) ($summary['late'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTodayWithUsers(string $date, int $limit = 5): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                `attendances`.`id`,
                `attendances`.`check_in`,
                `attendances`.`check_out`,
                `attendances`.`status`,
                `users`.`name`,
                `users`.`employee_code`
             FROM `attendances`
             INNER JOIN `users` ON `users`.`id` = `attendances`.`user_id`
             WHERE `attendances`.`attendance_date` = :attendance_date
             ORDER BY COALESCE(`attendances`.`check_in`, `attendances`.`created_at`) DESC,
                      `attendances`.`id` DESC
             LIMIT :result_limit'
        );
        $statement->bindValue('attendance_date', $date);
        $statement->bindValue('result_limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForUserByDate(int $userId, string $date): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `attendance_date`, `check_in`, `check_out`, `status`, `late_minutes`
             FROM `attendances`
             WHERE `user_id` = :user_id AND `attendance_date` = :attendance_date
             LIMIT 1'
        );
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('attendance_date', $date);
        $statement->execute();
        $attendance = $statement->fetch();

        return is_array($attendance) ? $attendance : null;
    }

    /** @return array<string, mixed>|null */
    public function findForUserByDateForUpdate(int $userId, string $date): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `attendance_date`, `check_in`, `check_out`, `status`, `late_minutes`
             FROM `attendances`
             WHERE `user_id` = :user_id AND `attendance_date` = :attendance_date
             LIMIT 1
             FOR UPDATE'
        );
        $statement->execute([
            'user_id' => $userId,
            'attendance_date' => $date,
        ]);
        $attendance = $statement->fetch();

        return is_array($attendance) ? $attendance : null;
    }

    /** @param array<string, int|float|string> $data */
    public function createCheckIn(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `attendances` (
                `user_id`, `work_location_id`, `attendance_date`, `check_in`, `check_in_photo`,
                `check_in_latitude`, `check_in_longitude`, `check_in_accuracy`, `check_in_distance`,
                `status`, `late_minutes`
             ) VALUES (
                :user_id, :work_location_id, :attendance_date, :check_in, :check_in_photo,
                :check_in_latitude, :check_in_longitude, :check_in_accuracy, :check_in_distance,
                :status, :late_minutes
             )'
        );
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, int|float|string> $data */
    public function fillMissingCheckIn(int $attendanceId, int $userId, array $data): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE `attendances`
             SET `work_location_id` = :work_location_id,
                 `check_in` = :check_in,
                 `check_in_photo` = :check_in_photo,
                 `check_in_latitude` = :check_in_latitude,
                 `check_in_longitude` = :check_in_longitude,
                 `check_in_accuracy` = :check_in_accuracy,
                 `check_in_distance` = :check_in_distance,
                 `status` = :status,
                 `late_minutes` = :late_minutes
             WHERE `id` = :id AND `user_id` = :user_id AND `check_in` IS NULL'
        );
        $statement->execute($data + [
            'id' => $attendanceId,
            'user_id' => $userId,
        ]);

        return $statement->rowCount() === 1;
    }

    /** @param array<string, int|float|string> $data */
    public function completeCheckOut(int $attendanceId, int $userId, array $data): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE `attendances`
             SET `check_out` = :check_out,
                 `check_out_photo` = :check_out_photo,
                 `check_out_latitude` = :check_out_latitude,
                 `check_out_longitude` = :check_out_longitude,
                 `check_out_accuracy` = :check_out_accuracy,
                 `check_out_distance` = :check_out_distance
             WHERE `id` = :id
               AND `user_id` = :user_id
               AND `check_in` IS NOT NULL
               AND `check_out` IS NULL'
        );
        $statement->execute($data + [
            'id' => $attendanceId,
            'user_id' => $userId,
        ]);

        return $statement->rowCount() === 1;
    }

    /**
     * @return array{present: int, late: int}
     */
    public function getMonthlyStatusSummary(int $userId, string $monthStart, string $monthEnd): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN `status` = :present_status THEN 1 ELSE 0 END), 0) AS `present`,
                COALESCE(SUM(CASE WHEN `status` = :late_status THEN 1 ELSE 0 END), 0) AS `late`
             FROM `attendances`
             WHERE `user_id` = :user_id
               AND `attendance_date` BETWEEN :month_start AND :month_end'
        );
        $statement->bindValue('present_status', 'present');
        $statement->bindValue('late_status', 'late');
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('month_start', $monthStart);
        $statement->bindValue('month_end', $monthEnd);
        $statement->execute();
        $summary = $statement->fetch();

        return [
            'present' => (int) ($summary['present'] ?? 0),
            'late' => (int) ($summary['late'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentForUser(int $userId, int $limit = 5): array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `attendance_date`, `check_in`, `check_out`, `status`, `late_minutes`
             FROM `attendances`
             WHERE `user_id` = :user_id
             ORDER BY `attendance_date` DESC, `id` DESC
             LIMIT :result_limit'
        );
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('result_limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function getForUserDateRange(int $userId, string $startDate, string $endDate): array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.`id`, a.`attendance_date`, a.`check_in`, a.`check_out`,
                    a.`status`, a.`late_minutes`, a.`work_location_id`,
                    wl.`name` AS `work_location_name`
             FROM `attendances` AS a
             LEFT JOIN `work_locations` AS wl ON wl.`id` = a.`work_location_id`
             WHERE a.`user_id` = :user_id
               AND a.`attendance_date` BETWEEN :start_date AND :end_date
             ORDER BY a.`attendance_date` ASC, a.`id` ASC'
        );
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('start_date', $startDate);
        $statement->bindValue('end_date', $endDate);
        $statement->execute();

        return $statement->fetchAll();
    }
}
