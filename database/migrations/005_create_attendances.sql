CREATE TABLE `attendances` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `work_location_id` BIGINT UNSIGNED NULL,
    `attendance_date` DATE NOT NULL,
    `check_in` DATETIME NULL,
    `check_in_photo` VARCHAR(255) NULL,
    `check_in_latitude` DECIMAL(10,7) NULL,
    `check_in_longitude` DECIMAL(10,7) NULL,
    `check_in_accuracy` DECIMAL(8,2) NULL,
    `check_in_distance` DECIMAL(8,2) NULL,
    `check_out` DATETIME NULL,
    `check_out_photo` VARCHAR(255) NULL,
    `check_out_latitude` DECIMAL(10,7) NULL,
    `check_out_longitude` DECIMAL(10,7) NULL,
    `check_out_accuracy` DECIMAL(8,2) NULL,
    `check_out_distance` DECIMAL(8,2) NULL,
    `status` ENUM('present', 'late') NOT NULL DEFAULT 'present',
    `late_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_attendances_user_date` (`user_id`, `attendance_date`),
    KEY `idx_attendances_work_location_id` (`work_location_id`),
    CONSTRAINT `fk_attendances_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_attendances_work_location`
        FOREIGN KEY (`work_location_id`) REFERENCES `work_locations` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
