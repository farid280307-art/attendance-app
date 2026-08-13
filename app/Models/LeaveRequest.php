<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class LeaveRequest
{
    public function __construct(private PDO $pdo)
    {
    }

    public function countPending(): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM `leave_requests` WHERE `status` = :status'
        );
        $statement->execute(['status' => 'pending']);

        return (int) $statement->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentWithUsers(int $limit = 5): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                `leave_requests`.`id`,
                `leave_requests`.`type`,
                `leave_requests`.`start_date`,
                `leave_requests`.`end_date`,
                `leave_requests`.`status`,
                `users`.`name`,
                `users`.`employee_code`
             FROM `leave_requests`
             INNER JOIN `users` ON `users`.`id` = `leave_requests`.`user_id`
             ORDER BY `leave_requests`.`created_at` DESC, `leave_requests`.`id` DESC
             LIMIT :result_limit'
        );
        $statement->bindValue('result_limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Menghitung jumlah pengajuan approved yang tanggalnya beririsan dengan bulan berjalan.
     *
     * @return array{leave: int, sick: int, permission: int}
     */
    public function getApprovedMonthlyTypeSummary(
        int $userId,
        string $monthStart,
        string $monthEnd
    ): array {
        $statement = $this->pdo->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN `type` = :leave_type THEN 1 ELSE 0 END), 0) AS `leave_count`,
                COALESCE(SUM(CASE WHEN `type` = :sick_type THEN 1 ELSE 0 END), 0) AS `sick_count`,
                COALESCE(SUM(CASE WHEN `type` = :permission_type THEN 1 ELSE 0 END), 0) AS `permission_count`
             FROM `leave_requests`
             WHERE `user_id` = :user_id
               AND `status` = :approved_status
               AND `start_date` <= :month_end
               AND `end_date` >= :month_start'
        );
        $statement->bindValue('leave_type', 'leave');
        $statement->bindValue('sick_type', 'sick');
        $statement->bindValue('permission_type', 'permission');
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('approved_status', 'approved');
        $statement->bindValue('month_end', $monthEnd);
        $statement->bindValue('month_start', $monthStart);
        $statement->execute();
        $summary = $statement->fetch();

        return [
            'leave' => (int) ($summary['leave_count'] ?? 0),
            'sick' => (int) ($summary['sick_count'] ?? 0),
            'permission' => (int) ($summary['permission_count'] ?? 0),
        ];
    }
}
