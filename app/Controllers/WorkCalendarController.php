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
        $rawEmployeeId = $_GET['employee_id'] ?? null;

        if ($rawEmployeeId !== null && $rawEmployeeId !== '') {
            $employeeId = \positive_int($rawEmployeeId);
            $selectedEmployee = $employeeId === null ? null : (new User(\db()))->findEmployeeById($employeeId);

            if ($selectedEmployee === null || (int) $selectedEmployee['is_active'] !== 1) {
                \abort(404, 'errors.404');
                return;
            }

            $period = $this->period($month['year'], $month['month'], $now);
            $coverage = (new WorkCalendar(\db()))->getMonthCoverage(
                $employeeId,
                $period['start'],
                $period['end'],
                $period['days']
            );
        }

        \view('admin.work-calendar.index', [
            'user' => \auth_user(),
            'employees' => $employees,
            'selectedEmployee' => $selectedEmployee,
            'selectedMonth' => $month['value'],
            'monthError' => $month['error'],
            'coverage' => $coverage,
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
