<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\WorkCalendar;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use PDO;
use RuntimeException;
use Throwable;

final class WorkScheduleService
{
    private User $users;
    private WorkCalendar $calendars;
    private WorkSchedule $schedules;
    private WorkScheduleDay $scheduleDays;

    public function __construct(private PDO $pdo)
    {
        $this->users = new User($pdo);
        $this->calendars = new WorkCalendar($pdo);
        $this->schedules = new WorkSchedule($pdo);
        $this->scheduleDays = new WorkScheduleDay($pdo);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->transaction(function () use ($data): int {
            $id = $this->schedules->create($this->scheduleData($data));
            $this->scheduleDays->replaceForSchedule($id, $data['working_days']);

            return $id;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(int $scheduleId, array $data, string $today): void
    {
        $this->transaction(function () use ($scheduleId, $data, $today): void {
            $this->schedules->update($scheduleId, $this->scheduleData($data));
            $this->scheduleDays->replaceForSchedule($scheduleId, $data['working_days']);
            $this->calendars->deleteFutureBySchedule($scheduleId, $today);
        });
    }

    public function setActive(int $scheduleId, bool $active, string $today): void
    {
        $this->transaction(function () use ($scheduleId, $active, $today): void {
            $this->schedules->setActive($scheduleId, $active);
            $this->calendars->deleteFutureBySchedule($scheduleId, $today);
        });
    }

    public function assign(int $employeeId, int $scheduleId, string $today): void
    {
        $this->transaction(function () use ($employeeId, $scheduleId, $today): void {
            if (!$this->users->assignWorkSchedule($employeeId, $scheduleId)) {
                throw new RuntimeException('Karyawan aktif tidak dapat diperbarui.');
            }
            $this->calendars->deleteFromDateForUser($employeeId, $today);
        });
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function scheduleData(array $data): array
    {
        return [
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'late_tolerance_minutes' => $data['late_tolerance_minutes'],
            'is_active' => $data['is_active'],
        ];
    }

    private function transaction(callable $operation): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $operation();
            $this->pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
