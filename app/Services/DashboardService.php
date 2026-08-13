<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use DateTimeImmutable;
use PDO;

final class DashboardService
{
    private Attendance $attendances;
    private LeaveRequest $leaveRequests;
    private User $users;

    public function __construct(PDO $pdo)
    {
        $this->attendances = new Attendance($pdo);
        $this->leaveRequests = new LeaveRequest($pdo);
        $this->users = new User($pdo);
    }

    /**
     * @return array{active_employees: int, checked_in_today: int, late_today: int, pending_leaves: int}
     */
    public function getAdminSummary(DateTimeImmutable $now): array
    {
        $attendanceSummary = $this->attendances->getTodaySummary($now->format('Y-m-d'));

        return [
            'active_employees' => $this->users->countActiveEmployees(),
            'checked_in_today' => $attendanceSummary['checked_in'],
            'late_today' => $attendanceSummary['late'],
            'pending_leaves' => $this->leaveRequests->countPending(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTodayAttendances(DateTimeImmutable $now, int $limit = 5): array
    {
        return $this->attendances->getTodayWithUsers($now->format('Y-m-d'), $limit);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentLeaveRequests(int $limit = 5): array
    {
        return $this->leaveRequests->getRecentWithUsers($limit);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEmployeeTodayAttendance(int $userId, DateTimeImmutable $now): ?array
    {
        return $this->attendances->findForUserByDate($userId, $now->format('Y-m-d'));
    }

    /**
     * @return array{state: string, label: string, attendance: array<string, mixed>|null}
     */
    public function getEmployeeTodayStatus(int $userId, DateTimeImmutable $now): array
    {
        $attendance = $this->getEmployeeTodayAttendance($userId, $now);

        if ($attendance === null || $attendance['check_in'] === null) {
            return [
                'state' => 'not_checked_in',
                'label' => 'Belum Absen',
                'attendance' => $attendance,
            ];
        }

        if ($attendance['check_out'] === null) {
            return [
                'state' => 'checked_in',
                'label' => 'Sudah Absen Masuk',
                'attendance' => $attendance,
            ];
        }

        return [
            'state' => 'completed',
            'label' => 'Absensi Selesai',
            'attendance' => $attendance,
        ];
    }

    /**
     * @return array{present: int, late: int, leave: int, sick: int, permission: int}
     */
    public function getEmployeeMonthlySummary(int $userId, DateTimeImmutable $now): array
    {
        $monthStart = $now->modify('first day of this month')->format('Y-m-d');
        $monthEnd = $now->modify('last day of this month')->format('Y-m-d');
        $attendanceSummary = $this->attendances->getMonthlyStatusSummary(
            $userId,
            $monthStart,
            $monthEnd
        );
        $leaveSummary = $this->leaveRequests->getApprovedMonthlyTypeSummary(
            $userId,
            $monthStart,
            $monthEnd
        );

        return array_merge($attendanceSummary, $leaveSummary);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEmployeeRecentAttendances(int $userId, int $limit = 5): array
    {
        return $this->attendances->getRecentForUser($userId, $limit);
    }
}
