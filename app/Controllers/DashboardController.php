<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DashboardService;
use DateTimeImmutable;
use DateTimeZone;

final class DashboardController
{
    public function index(): void
    {
        $user = \auth_user();

        if ($user === null) {
            \redirect('/login');
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
        $dashboard = new DashboardService(\db());
        $commonData = [
            'user' => $user,
            'success' => \flash('success'),
            'todayLabel' => \indonesian_date($now),
        ];

        if ($user['role'] === 'admin') {
            \view('admin.dashboard', array_merge($commonData, [
                'summary' => $dashboard->getAdminSummary($now),
                'todayAttendances' => $dashboard->getTodayAttendances($now),
                'recentLeaveRequests' => $dashboard->getRecentLeaveRequests(),
            ]));
            return;
        }

        $userId = (int) $user['id'];

        \view('employee.dashboard', array_merge($commonData, [
            'greeting' => \time_greeting($now),
            'monthLabel' => \indonesian_month_year($now),
            'todayStatus' => $dashboard->getEmployeeTodayStatus($userId, $now),
            'monthlySummary' => $dashboard->getEmployeeMonthlySummary($userId, $now),
            'recentAttendances' => $dashboard->getEmployeeRecentAttendances($userId),
        ]));
    }

    public function adminTest(): void
    {
        \view('admin.test');
    }
}
