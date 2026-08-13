<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Services\WorkCalendarException;
use App\Services\WorkCalendarService;
use App\Validators\MonthValidator;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class WorkCalendarController
{
    public function index(): void
    {
        $now = $this->now();
        $month = (new MonthValidator())->validate($_GET['month'] ?? null, $now);
        $employees = (new User(\db()))->getActiveEmployeesWithSchedule();
        $selectedEmployee = null;
        $coverage = null;
        $calendarDays = [];
        $calendarCells = [];
        $calendarSummary = null;
        $calendarMonthLabel = null;
        $rawEmployeeId = $_GET['employee_id'] ?? null;

        if ($rawEmployeeId !== null && $rawEmployeeId !== '') {
            $employeeId = \positive_int($rawEmployeeId);
            $selectedEmployee = $employeeId === null ? null : (new User(\db()))->findEmployeeById($employeeId);

            if ($selectedEmployee === null || (int) $selectedEmployee['is_active'] !== 1) {
                \abort(404, 'errors.404');
                return;
            }

            $period = $this->period($month['year'], $month['month'], $now);
            $calendarRows = (new WorkCalendar(\db()))->getForUserDateRange(
                $employeeId,
                $period['start'],
                $period['end']
            );
            $calendar = $this->buildCalendarPresentation(
                $month['year'],
                $month['month'],
                $calendarRows,
                $now
            );
            $calendarDays = $calendar['days'];
            $calendarCells = $calendar['cells'];
            $calendarSummary = $calendar['summary'];
            $calendarMonthLabel = $calendar['month_label'];
            $coverage = [
                'covered_days' => $calendar['covered_days'],
                'total_days' => $period['days'],
                'complete' => $calendar['covered_days'] === $period['days'],
            ];
        }

        \view('admin.work-calendar.index', [
            'user' => \auth_user(),
            'employees' => $employees,
            'selectedEmployee' => $selectedEmployee,
            'selectedMonth' => $month['value'],
            'monthError' => $month['error'],
            'coverage' => $coverage,
            'calendarDays' => $calendarDays,
            'calendarCells' => $calendarCells,
            'calendarSummary' => $calendarSummary,
            'calendarMonthLabel' => $calendarMonthLabel,
            'pastPeriodWarning' => $this->period($month['year'], $month['month'], $now)['start'] < $now->format('Y-m-d'),
            'success' => \flash('success'),
            'error' => \flash('error'),
        ]);
    }

    public function generate(): void
    {
        if (!\verify_csrf()) {
            \abort(419, 'errors.419');
            return;
        }

        $now = $this->now();
        $month = (new MonthValidator())->validate($_POST['month'] ?? null, $now);
        $employeeId = \positive_int($_POST['employee_id'] ?? null);

        if ($month['error'] !== null || $employeeId === null) {
            \flash('error', 'Bulan atau karyawan yang dipilih tidak valid.');
            \redirect('/admin/work-calendar');
        }

        try {
            $result = (new WorkCalendarService(\db()))->generateForEmployeeMonth(
                $employeeId,
                $month['year'],
                $month['month'],
                $now
            );
        } catch (WorkCalendarException $exception) {
            \flash('error', $exception->getMessage());
            \redirect('/admin/work-calendar?month=' . $month['value'] . '&employee_id=' . $employeeId);
        } catch (Throwable $exception) {
            error_log('Generate kalender kerja gagal: ' . $exception->getMessage());
            \flash('error', 'Kalender kerja tidak dapat dibuat saat ini.');
            \redirect('/admin/work-calendar?month=' . $month['value'] . '&employee_id=' . $employeeId);
        }

        $this->logGenerated($result, $this->requestContext());
        \flash('success', sprintf(
            'Kalender kerja berhasil dibuat: %d tanggal, %d hari kerja, %d libur mingguan, dan %d hari libur.',
            $result['processed_days'],
            $result['work_days'],
            $result['off_days'],
            $result['holiday_days']
        ));
        \redirect('/admin/work-calendar?month=' . $month['value'] . '&employee_id=' . $employeeId);
    }

    /** @return array{start:string,end:string,days:int} */
    private function period(int $year, int $month, DateTimeImmutable $now): array
    {
        $start = DateTimeImmutable::createFromFormat('!Y-n-j', sprintf('%d-%d-1', $year, $month), $now->getTimezone());

        if ($start === false) {
            throw new \InvalidArgumentException('Periode tidak valid.');
        }

        $end = $start->modify('last day of this month');

        return ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d'), 'days' => (int) $end->format('j')];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{
     *     days:array<int, array<string, mixed>>,
     *     cells:array<int, array<string, mixed>|null>,
     *     summary:array{work:int,off:int,holiday:int,missing:int},
     *     covered_days:int,
     *     month_label:string
     * }
     */
    private function buildCalendarPresentation(
        int $year,
        int $month,
        array $rows,
        DateTimeImmutable $now
    ): array {
        $start = DateTimeImmutable::createFromFormat(
            '!Y-n-j',
            sprintf('%d-%d-1', $year, $month),
            $now->getTimezone()
        );

        if ($start === false) {
            throw new \InvalidArgumentException('Periode kalender tidak valid.');
        }

        $end = $start->modify('last day of this month');
        $rowMap = [];

        foreach ($rows as $row) {
            $workDate = $row['work_date'] ?? null;

            if (is_string($workDate)) {
                $rowMap[$workDate] = $row;
            }
        }

        $summary = ['work' => 0, 'off' => 0, 'holiday' => 0, 'missing' => 0];
        $statusLabels = [
            'work' => 'Kerja',
            'off' => 'Libur',
            'holiday' => 'Hari Libur',
        ];
        $days = [];

        for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
            $dateValue = $date->format('Y-m-d');
            $row = $rowMap[$dateValue] ?? null;
            $dayType = is_array($row) && isset($statusLabels[$row['day_type'] ?? null])
                ? (string) $row['day_type']
                : null;
            $generated = $dayType !== null;
            $summary[$generated ? $dayType : 'missing']++;
            $statusLabel = $generated ? $statusLabels[$dayType] : 'Belum Digenerate';
            $scheduleName = $generated && is_string($row['schedule_name'] ?? null)
                ? $row['schedule_name']
                : null;
            $holidayName = $dayType === 'holiday' && is_string($row['holiday_name'] ?? null)
                ? $row['holiday_name']
                : null;
            $accessibleDescription = \indonesian_date($date) . ', ' . $statusLabel;

            if ($holidayName !== null && $holidayName !== '') {
                $accessibleDescription .= ', ' . $holidayName;
            } elseif ($dayType === 'work' && $scheduleName !== null && $scheduleName !== '') {
                $accessibleDescription .= ', jadwal ' . $scheduleName;
            }

            if ($dateValue === $now->format('Y-m-d')) {
                $accessibleDescription .= ', hari ini';
            }

            $days[] = [
                'date' => $dateValue,
                'day' => (int) $date->format('j'),
                'weekday' => (int) $date->format('N'),
                'day_type' => $dayType,
                'status_label' => $statusLabel,
                'schedule_name' => $scheduleName,
                'holiday_name' => $holidayName,
                'generated' => $generated,
                'is_today' => $dateValue === $now->format('Y-m-d'),
                'accessible_description' => $accessibleDescription,
            ];
        }

        $cells = array_fill(0, (int) $start->format('N') - 1, null);

        foreach ($days as $day) {
            $cells[] = $day;
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return [
            'days' => $days,
            'cells' => $cells,
            'summary' => $summary,
            'covered_days' => count($days) - $summary['missing'],
            'month_label' => \indonesian_month_year($start),
        ];
    }

    /** @param array<string,mixed> $result @param array{ip_address:?string,user_agent:?string} $context */
    private function logGenerated(array $result, array $context): void
    {
        try {
            (new ActivityLog(\db()))->create(
                \auth_id(),
                'work_calendar.generated',
                sprintf(
                    'Admin generate kalender kerja %s untuk %s.',
                    $result['employee']['employee_code'],
                    $result['period']['label']
                ),
                $context['ip_address'],
                $context['user_agent']
            );
        } catch (Throwable $exception) {
            error_log('Activity log kalender kerja gagal disimpan: ' . $exception->getMessage());
        }
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
    }

    /** @return array{ip_address:?string,user_agent:?string} */
    private function requestContext(): array
    {
        return [
            'ip_address' => $this->serverValue('REMOTE_ADDR', 45),
            'user_agent' => $this->serverValue('HTTP_USER_AGENT', 2000),
        ];
    }

    private function serverValue(string $key, int $maxLength): ?string
    {
        $value = $_SERVER[$key] ?? null;

        return is_string($value) && $value !== '' ? substr($value, 0, $maxLength) : null;
    }
}
