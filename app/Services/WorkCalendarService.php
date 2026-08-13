<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Holiday;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use DateTimeImmutable;
use PDO;
use Throwable;

final class WorkCalendarService
{
    private Holiday $holidays;
    private User $users;
    private WorkCalendar $calendars;
    private WorkSchedule $schedules;
    private WorkScheduleDay $scheduleDays;

    public function __construct(private PDO $pdo)
    {
        $this->holidays = new Holiday($pdo);
        $this->users = new User($pdo);
        $this->calendars = new WorkCalendar($pdo);
        $this->schedules = new WorkSchedule($pdo);
        $this->scheduleDays = new WorkScheduleDay($pdo);
    }

    /**
     * @return array{processed_days:int,work_days:int,off_days:int,holiday_days:int,preserved_historical:int,employee:array<string,mixed>,period:array<string,string>}
     */
    public function generateForEmployeeMonth(
        int $userId,
        int $year,
        int $month,
        DateTimeImmutable $now
    ): array {
        if ($userId < 1 || $year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            throw new WorkCalendarException('Parameter kalender kerja tidak valid.');
        }

        $employee = $this->users->findEmployeeById($userId);

        if ($employee === null) {
            throw new WorkCalendarException('Karyawan tidak ditemukan.');
        }

        $scheduleId = (int) ($employee['work_schedule_id'] ?? 0);

        if ($scheduleId < 1) {
            throw new WorkCalendarException('Jadwal kerja karyawan belum ditentukan.');
        }

        $schedule = $this->schedules->findById($scheduleId);

        if ($schedule === null) {
            throw new WorkCalendarException('Jadwal kerja karyawan tidak ditemukan.');
        }

        $weekdays = $this->scheduleDays->getWeekdaysForSchedule($scheduleId);

        if ($weekdays === []) {
            throw new WorkCalendarException('Hari kerja untuk jadwal ini belum dikonfigurasi.');
        }

        $start = DateTimeImmutable::createFromFormat(
            '!Y-n-j',
            sprintf('%d-%d-1', $year, $month),
            $now->getTimezone()
        );

        if ($start === false) {
            throw new WorkCalendarException('Periode kalender kerja tidak valid.');
        }

        $end = $start->modify('last day of this month');
        $today = $now->format('Y-m-d');
        $startValue = $start->format('Y-m-d');
        $endValue = $end->format('Y-m-d');
        $holidayMap = [];

        foreach ($this->holidays->getActiveForRange($startValue, $endValue) as $holiday) {
            $holidayMap[(string) $holiday['holiday_date']] = $holiday;
        }

        $existingMap = [];

        foreach ($this->calendars->getForUserDateRange($userId, $startValue, $endValue) as $row) {
            $existingMap[(string) $row['work_date']] = $row;
        }

        $counts = ['work' => 0, 'off' => 0, 'holiday' => 0];
        $preserved = 0;
        $generatedAt = $now->format('Y-m-d H:i:s');
        $this->pdo->beginTransaction();

        try {
            for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
                $dateValue = $date->format('Y-m-d');
                $existing = $existingMap[$dateValue] ?? null;

                if ($dateValue < $today && $existing !== null) {
                    $counts[(string) $existing['day_type']]++;
                    $preserved++;
                    continue;
                }

                $holiday = $holidayMap[$dateValue] ?? null;
                $dayType = $holiday !== null
                    ? 'holiday'
                    : (in_array((int) $date->format('N'), $weekdays, true) ? 'work' : 'off');
                $data = [
                    'user_id' => $userId,
                    'work_date' => $dateValue,
                    'work_schedule_id' => $scheduleId,
                    'day_type' => $dayType,
                    'schedule_name' => (string) $schedule['name'],
                    'holiday_name' => $holiday['name'] ?? null,
                    'generated_at' => $generatedAt,
                ];

                if ($dateValue < $today) {
                    $inserted = $this->calendars->insertGeneratedDayIfMissing($data);

                    if (!$inserted) {
                        $current = $this->calendars->findForUserDate($userId, $dateValue);
                        $counts[(string) ($current['day_type'] ?? $dayType)]++;
                        $preserved++;
                        continue;
                    }
                } else {
                    $this->calendars->upsertGeneratedDay($data);
                }

                $counts[$dayType]++;
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        return [
            'processed_days' => (int) $end->format('j'),
            'work_days' => $counts['work'],
            'off_days' => $counts['off'],
            'holiday_days' => $counts['holiday'],
            'preserved_historical' => $preserved,
            'employee' => $employee,
            'period' => ['value' => $start->format('Y-m'), 'label' => \indonesian_month_year($start)],
        ];
    }
}
