CREATE TABLE `leave_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `type` ENUM('leave', 'sick', 'permission') NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `reason` TEXT NOT NULL,
    `attachment` VARCHAR(255) NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `reviewed_by` BIGINT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `rejection_reason` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_leave_requests_user_id` (`user_id`),
    KEY `idx_leave_requests_reviewed_by` (`reviewed_by`),
    CONSTRAINT `chk_leave_requests_date_range`
        CHECK (`end_date` >= `start_date`),
    CONSTRAINT `fk_leave_requests_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_leave_requests_reviewer`
        FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
