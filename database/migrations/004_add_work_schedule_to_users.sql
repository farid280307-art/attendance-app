ALTER TABLE `users`
    ADD KEY `idx_users_work_schedule_id` (`work_schedule_id`),
    ADD CONSTRAINT `fk_users_work_schedule`
        FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`)
        ON DELETE SET NULL;
