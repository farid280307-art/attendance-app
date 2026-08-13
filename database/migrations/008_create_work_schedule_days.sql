CREATE TABLE `work_schedule_days` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `work_schedule_id` BIGINT UNSIGNED NOT NULL,
    `weekday` TINYINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_work_schedule_days_schedule_weekday` (`work_schedule_id`, `weekday`),
    CONSTRAINT `chk_work_schedule_days_weekday`
        CHECK (`weekday` BETWEEN 1 AND 7),
    CONSTRAINT `fk_work_schedule_days_schedule`
        FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
