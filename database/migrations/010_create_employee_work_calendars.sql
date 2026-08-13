CREATE TABLE `employee_work_calendars` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `work_date` DATE NOT NULL,
    `work_schedule_id` BIGINT UNSIGNED NULL,
    `day_type` ENUM('work', 'off', 'holiday') NOT NULL,
    `schedule_name` VARCHAR(100) NULL,
    `holiday_name` VARCHAR(150) NULL,
    `generated_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_employee_work_calendars_user_date` (`user_id`, `work_date`),
    KEY `idx_employee_work_calendars_schedule_date` (`work_schedule_id`, `work_date`),
    KEY `idx_employee_work_calendars_work_date` (`work_date`),
    CONSTRAINT `fk_employee_work_calendars_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_employee_work_calendars_schedule`
        FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
