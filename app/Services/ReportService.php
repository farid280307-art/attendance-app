<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\WorkCalendar;
use DateTimeImmutable;
use PDO;

final class ReportService
{
    private const STATUS_LABELS = [
        'present' => 'Hadir',
        'late' => 'Terlambat',
        'leave' => 'Cuti',
        'sick' => 'Sakit',
        'permission' => 'Izin',
        'alpha' => 'Alpha',
        'off' => 'Libur',
        'holiday' => 'Hari Libur',
        'no_record' => 'Tidak Ada Data',
        'future' => 'Belum Berlangsung',
    ];

    private const DAY_NAMES = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
        5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
    ];

    private Attendance $attendances;
    private LeaveRequest $leaveRequests;
    private WorkCalendar $calendars;

    public function __construct(PDO $pdo)
    {
        $this->attendances = new Attendance($pdo);
        $this->leaveRequests = new LeaveRequest($pdo);
        $this->calendars = new WorkCalendar($pdo);
    }

    /** @return array<string,mixed> */
    public function getMonthlyReport(
        int $userId,
        int $year,
        int $month,
        DateTimeImmutable $now
    ): array {
        if ($userId < 1 || $year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Parameter laporan tidak valid.');
        }

        $startDate = DateTimeImmutable::createFromFormat(
            '!Y-n-j',
            sprintf('%d-%d-1', $year, $month),
            $now->getTimezone()
        );

        if ($startDate === false) {
            throw new \InvalidArgumentException('Periode laporan tidak valid.');
        }

        $endDate = $startDate->modify('last day of this month');
        $startValue = $startDate->format('Y-m-d');
        $endValue = $endDate->format('Y-m-d');
        $todayValue = $now->format('Y-m-d');
        $attendanceMap = $this->indexRows(
            $this->attendances->getForUserDateRange($userId, $startValue, $endValue),
            'attendance_date'
        );
        $leaveMap = $this->expandApprovedLeaves(
            $this->leaveRequests->getApprovedForUserDateRange($userId, $startValue, $endValue),
            $startDate,
            $endDate
        );
        $calendarMap = $this->indexRows(
            $this->calendars->getForUserDateRange($userId, $startValue, $endValue),
            'work_date'
        );
        $summary = [
            'present' => 0,
            'late' => 0,
            'leave' => 0,
            'sick' => 0,
            'permission' => 0,
            'alpha' => 0,
            'off' => 0,
            'holiday' => 0,
            'no_record' => 0,
            'total_attendance' => 0,
        ];
        $days = [];

        for ($date = $startDate; $date <= $endDate; $date = $date->modify('+1 day')) {
            $dateValue = $date->format('Y-m-d');
            $attendance = $attendanceMap[$dateValue] ?? null;
            $leave = $leaveMap[$dateValue] ?? null;
            $calendar = $calendarMap[$dateValue] ?? null;
            $status = $this->resolveStatus($dateValue, $todayValue, $attendance, $leave, $calendar);

            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            }

            if ($status === 'present' || $status === 'late') {
                $summary['total_attendance']++;
            }

            $days[] = [
                'date' => $dateValue,
                'day_name' => self::DAY_NAMES[(int) $date->format('N')],
                'status' => $status,
                'status_label' => self::STATUS_LABELS[$status],
                'check_in' => $attendance['check_in'] ?? null,
                'check_out' => $attendance['check_out'] ?? null,
                'late_minutes' => $attendance === null ? null : (int) $attendance['late_minutes'],
                'work_location_id' => $attendance === null || $attendance['work_location_id'] === null
                    ? null
                    : (int) $attendance['work_location_id'],
                'work_location_name' => $attendance['work_location_name'] ?? null,
                'leave_type' => in_array($status, ['leave', 'sick', 'permission'], true)
                    ? ($leave['type'] ?? null)
                    : null,
                'day_type' => $calendar['day_type'] ?? null,
                'schedule_name' => $calendar['schedule_name'] ?? null,
                'holiday_name' => $calendar['holiday_name'] ?? null,
            ];
        }

        $totalDays = (int) $endDate->format('j');

        return [
            'period' => [
                'month' => $startDate->format('Y-m'),
                'label' => \indonesian_month_year($startDate),
                'start_date' => $startValue,
                'end_date' => $endValue,
                'total_days' => $totalDays,
            ],
            'calendar_coverage' => [
                'covered_days' => count($calendarMap),
                'total_days' => $totalDays,
                'complete' => count($calendarMap) === $totalDays,
            ],
            'summary' => $summary,
            'days' => $days,
        ];
    }

    /** @param array<string,mixed>|null $attendance @param array<string,mixed>|null $leave @param array<string,mixed>|null $calendar */
    private function resolveStatus(
        string $date,
        string $today,
        ?array $attendance,
        ?array $leave,
        ?array $calendar
    ): string {
        if ($attendance !== null) {
            return (string) $attendance['status'];
        }

        if ($calendar === null) {
            if ($leave !== null) {
                return (string) $leave['type'];
            }

            return $date > $today ? 'future' : 'no_record';
        }

        $dayType = (string) $calendar['day_type'];

        if ($dayType === 'holiday') {
            return 'holiday';
        }

        if ($dayType === 'off') {
            return 'off';
        }

        if ($leave !== null) {
            return (string) $leave['type'];
        }

        return $date > $today ? 'future' : 'alpha';
    }

    /** @param array<int,array<string,mixed>> $rows @return array<string,array<string,mixed>> */
    private function indexRows(array $rows, string $dateKey): array
    {
        $map = [];

        foreach ($rows as $row) {
            $date = (string) $row[$dateKey];

            if (!isset($map[$date])) {
                $map[$date] = $row;
            }
        }

        return $map;
    }

    /** @param array<int,array<string,mixed>> $rows @return array<string,array<string,mixed>> */
    private function expandApprovedLeaves(array $rows, DateTimeImmutable $periodStart, DateTimeImmutable $periodEnd): array
    {
        $map = [];
        $timezone = $periodStart->getTimezone();

        foreach ($rows as $row) {
            $leaveStart = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $row['start_date'], $timezone);
            $leaveEnd = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $row['end_date'], $timezone);

            if ($leaveStart === false || $leaveEnd === false) {
                continue;
            }

            $rangeStart = $leaveStart < $periodStart ? $periodStart : $leaveStart;
            $rangeEnd = $leaveEnd > $periodEnd ? $periodEnd : $leaveEnd;

            for ($date = $rangeStart; $date <= $rangeEnd; $date = $date->modify('+1 day')) {
                $dateValue = $date->format('Y-m-d');

                if (!isset($map[$dateValue])) {
                    $map[$dateValue] = $row;
                }
            }
        }

        return $map;
    }
}
