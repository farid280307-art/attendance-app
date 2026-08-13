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

    /** @return array<int, array<string, mixed>> */
    public function getForUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `user_id`, `type`, `start_date`, `end_date`, `reason`,
                    `attachment`, `status`, `reviewed_by`, `reviewed_at`,
                    `rejection_reason`, `created_at`, `updated_at`
             FROM `leave_requests`
             WHERE `user_id` = :user_id
             ORDER BY `created_at` DESC, `id` DESC'
        );
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `user_id`, `type`, `start_date`, `end_date`, `reason`,
                    `attachment`, `status`, `reviewed_by`, `reviewed_at`,
                    `rejection_reason`, `created_at`, `updated_at`
             FROM `leave_requests`
             WHERE `id` = :id
             LIMIT 1'
        );
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->execute();
        $request = $statement->fetch();

        return is_array($request) ? $request : null;
    }

    /** @return array<string, mixed>|null */
    public function findByIdWithUser(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT lr.`id`, lr.`user_id`, lr.`type`, lr.`start_date`, lr.`end_date`,
                    lr.`reason`, lr.`attachment`, lr.`status`, lr.`reviewed_by`,
                    lr.`reviewed_at`, lr.`rejection_reason`, lr.`created_at`,
                    lr.`updated_at`, u.`name`, u.`employee_code`,
                    reviewer.`name` AS `reviewer_name`
             FROM `leave_requests` AS lr
             INNER JOIN `users` AS u ON u.`id` = lr.`user_id`
             LEFT JOIN `users` AS reviewer ON reviewer.`id` = lr.`reviewed_by`
             WHERE lr.`id` = :id
             LIMIT 1'
        );
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->execute();
        $request = $statement->fetch();

        return is_array($request) ? $request : null;
    }

    /**
     * @param array{user_id:int,type:string,start_date:string,end_date:string,reason:string,attachment:?string} $data
     */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `leave_requests`
                (`user_id`, `type`, `start_date`, `end_date`, `reason`, `attachment`, `status`)
             VALUES
                (:user_id, :type, :start_date, :end_date, :reason, :attachment, :status)'
        );
        $statement->execute([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'],
            'attachment' => $data['attachment'],
            'status' => 'pending',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function hasOverlappingRequest(int $userId, string $startDate, string $endDate): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`
             FROM `leave_requests`
             WHERE `user_id` = :user_id
               AND `status` IN (:pending_status, :approved_status)
               AND `start_date` <= :end_date
               AND `end_date` >= :start_date
             LIMIT 1
             FOR UPDATE'
        );
        $statement->execute([
            'user_id' => $userId,
            'pending_status' => 'pending',
            'approved_status' => 'approved',
            'end_date' => $endDate,
            'start_date' => $startDate,
        ]);

        return $statement->fetchColumn() !== false;
    }

    /** @return array<int, array<string, mixed>> */
    public function getForAdmin(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT lr.`id`, lr.`user_id`, lr.`type`, lr.`start_date`, lr.`end_date`,
                    lr.`reason`, lr.`attachment`, lr.`status`, lr.`reviewed_by`,
                    lr.`reviewed_at`, lr.`rejection_reason`, lr.`created_at`,
                    u.`name`, u.`employee_code`, reviewer.`name` AS `reviewer_name`
             FROM `leave_requests` AS lr
             INNER JOIN `users` AS u ON u.`id` = lr.`user_id`
             LEFT JOIN `users` AS reviewer ON reviewer.`id` = lr.`reviewed_by`
             ORDER BY CASE WHEN lr.`status` = :pending_status THEN 0 ELSE 1 END,
                      lr.`created_at` DESC, lr.`id` DESC'
        );
        $statement->execute(['pending_status' => 'pending']);

        return $statement->fetchAll();
    }

    public function approvePending(int $id, int $reviewerId, string $reviewedAt): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE `leave_requests`
             SET `status` = :approved_status,
                 `reviewed_by` = :reviewed_by,
                 `reviewed_at` = :reviewed_at,
                 `rejection_reason` = NULL
             WHERE `id` = :id AND `status` = :pending_status'
        );
        $statement->execute([
            'approved_status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => $reviewedAt,
            'id' => $id,
            'pending_status' => 'pending',
        ]);

        return $statement->rowCount() === 1;
    }

    public function rejectPending(
        int $id,
        int $reviewerId,
        string $reviewedAt,
        string $rejectionReason
    ): bool {
        $statement = $this->pdo->prepare(
            'UPDATE `leave_requests`
             SET `status` = :rejected_status,
                 `reviewed_by` = :reviewed_by,
                 `reviewed_at` = :reviewed_at,
                 `rejection_reason` = :rejection_reason
             WHERE `id` = :id AND `status` = :pending_status'
        );
        $statement->execute([
            'rejected_status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => $reviewedAt,
            'rejection_reason' => $rejectionReason,
            'id' => $id,
            'pending_status' => 'pending',
        ]);

        return $statement->rowCount() === 1;
    }

    /** @return array<int, array<string, mixed>> */
    public function getApprovedForUserDateRange(
        int $userId,
        string $startDate,
        string $endDate
    ): array {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `type`, `start_date`, `end_date`, `created_at`
             FROM `leave_requests`
             WHERE `user_id` = :user_id
               AND `status` = :approved_status
               AND `start_date` <= :end_date
               AND `end_date` >= :start_date
             ORDER BY `id` ASC, `created_at` ASC'
        );
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('approved_status', 'approved');
        $statement->bindValue('end_date', $endDate);
        $statement->bindValue('start_date', $startDate);
        $statement->execute();

        return $statement->fetchAll();
    }
}
