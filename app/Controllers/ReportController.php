<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\ReportService;
use App\Validators\MonthValidator;
use DateTimeImmutable;
use DateTimeZone;

final class ReportController
{
    public function employeeMonthly(): void
    {
        $user = \auth_user();
        $userId = \auth_id();

        if ($user === null || $userId === null) {
            \redirect('/login');
        }

        $now = $this->now();
        $month = (new MonthValidator())->validate($_GET['month'] ?? null, $now);
        $report = (new ReportService(\db()))->getMonthlyReport(
            $userId,
            $month['year'],
            $month['month'],
            $now
        );

        \view('reports.monthly', [
            'user' => $user,
            'selectedMonth' => $month['value'],
            'monthError' => $month['error'],
            'report' => $report,
        ]);
    }

    public function adminMonthly(): void
    {
        $now = $this->now();
        $month = (new MonthValidator())->validate($_GET['month'] ?? null, $now);
        $userModel = new User(\db());
        $employees = $userModel->getEmployeesForReport();
        $selectedEmployee = null;
        $report = null;
        $rawEmployeeId = $_GET['employee_id'] ?? null;

        if ($rawEmployeeId !== null && $rawEmployeeId !== '') {
            $employeeId = \positive_int($rawEmployeeId);
            $selectedEmployee = $employeeId === null
                ? null
                : $userModel->findEmployeeForReport($employeeId);

            if ($selectedEmployee === null) {
                \abort(404, 'errors.404');
                return;
            }

            $report = (new ReportService(\db()))->getMonthlyReport(
                (int) $selectedEmployee['id'],
                $month['year'],
                $month['month'],
                $now
            );
        }

        \view('admin.reports.monthly', [
            'user' => \auth_user(),
            'employees' => $employees,
            'selectedEmployee' => $selectedEmployee,
            'selectedMonth' => $month['value'],
            'monthError' => $month['error'],
            'report' => $report,
        ]);
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
    }
}
