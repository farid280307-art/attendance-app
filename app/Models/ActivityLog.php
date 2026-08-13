<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class ActivityLog
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(
        ?int $userId,
        string $action,
        ?string $description,
        ?string $ipAddress,
        ?string $userAgent
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO `activity_logs`
                (`user_id`, `action`, `description`, `ip_address`, `user_agent`)
             VALUES
                (:user_id, :action, :description, :ip_address, :user_agent)'
        );
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
