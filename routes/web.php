<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AdminLeaveController;
use App\Controllers\AdminAttendanceController;
use App\Controllers\AuthController;
use App\Controllers\AttendanceController;
use App\Controllers\DashboardController;
use App\Controllers\HolidayController;
use App\Controllers\LeaveController;
use App\Controllers\ReportController;
use App\Controllers\UserController;
use App\Controllers\WorkCalendarController;
use App\Controllers\WorkLocationController;
use App\Controllers\WorkScheduleController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\EmployeeMiddleware;
use App\Middleware\GuestMiddleware;

if (!isset($router) || !$router instanceof Router) {
    throw new LogicException('Router belum diinisialisasi.');
}

$appName = (string) ($GLOBALS['config']['app']['name'] ?? 'Attendance App');
$authController = new AuthController();
$attendanceController = new AttendanceController();
$leaveController = new LeaveController();
$adminLeaveController = new AdminLeaveController();
$adminAttendanceController = new AdminAttendanceController();
$reportController = new ReportController();
$userController = new UserController();
$dashboardController = new DashboardController();
$holidayController = new HolidayController();
$workCalendarController = new WorkCalendarController();
$workLocationController = new WorkLocationController();
$workScheduleController = new WorkScheduleController();
$authMiddleware = new AuthMiddleware();
$employeeMiddleware = new EmployeeMiddleware();
$adminMiddleware = new AdminMiddleware();
$guestMiddleware = new GuestMiddleware();

$router->get('/', static function () use ($appName): void {
    view('home', [
        'pageTitle' => $appName,
        'heading' => $appName,
        'subtitle' => 'Sistem Absensi Karyawan',
        'showLoginButton' => true,
    ]);
});

$router->get('/login', $guestMiddleware->handle([$authController, 'showLogin']));
$router->post('/login', $guestMiddleware->handle([$authController, 'login']));
$router->post('/logout', $authMiddleware->handle([$authController, 'logout']));
$router->get('/dashboard', $authMiddleware->handle([$dashboardController, 'index']));
$router->get('/attendance', $employeeMiddleware->handle([$attendanceController, 'index']));
$router->post('/attendance/location-check', $employeeMiddleware->handle([$attendanceController, 'checkLocation']));
$router->post('/attendance/submit', $employeeMiddleware->handle([$attendanceController, 'submit']));
$router->get('/leave', $employeeMiddleware->handle([$leaveController, 'index']));
$router->get('/leave/create', $employeeMiddleware->handle([$leaveController, 'create']));
$router->post('/leave/store', $employeeMiddleware->handle([$leaveController, 'store']));
$router->get('/leave/show', $employeeMiddleware->handle([$leaveController, 'show']));
$router->get('/leave/attachment', $authMiddleware->handle([$leaveController, 'attachment']));
$router->get('/reports/monthly', $employeeMiddleware->handle([$reportController, 'employeeMonthly']));
$router->get('/admin/attendances', $adminMiddleware->handle([$adminAttendanceController, 'index']));
$router->get('/admin/attendances/show', $adminMiddleware->handle([$adminAttendanceController, 'show']));
$router->get('/admin/attendances/photo', $adminMiddleware->handle([$adminAttendanceController, 'photo']));
$router->get('/admin/leave-requests', $adminMiddleware->handle([$adminLeaveController, 'index']));
$router->get('/admin/leave-requests/show', $adminMiddleware->handle([$adminLeaveController, 'show']));
$router->post('/admin/leave-requests/approve', $adminMiddleware->handle([$adminLeaveController, 'approve']));
$router->post('/admin/leave-requests/reject', $adminMiddleware->handle([$adminLeaveController, 'reject']));
$router->get('/admin/reports/monthly', $adminMiddleware->handle([$reportController, 'adminMonthly']));
$router->get('/admin/employees', $adminMiddleware->handle([$userController, 'index']));
$router->get('/admin/employees/create', $adminMiddleware->handle([$userController, 'create']));
$router->post('/admin/employees/store', $adminMiddleware->handle([$userController, 'store']));
$router->get('/admin/employees/show', $adminMiddleware->handle([$userController, 'show']));
$router->get('/admin/employees/edit', $adminMiddleware->handle([$userController, 'edit']));
$router->post('/admin/employees/update', $adminMiddleware->handle([$userController, 'update']));
$router->post('/admin/employees/toggle', $adminMiddleware->handle([$userController, 'toggleStatus']));
$router->post('/admin/employees/reset-password', $adminMiddleware->handle([$userController, 'resetPassword']));
$router->get('/admin/holidays', $adminMiddleware->handle([$holidayController, 'index']));
$router->get('/admin/holidays/create', $adminMiddleware->handle([$holidayController, 'create']));
$router->post('/admin/holidays/store', $adminMiddleware->handle([$holidayController, 'store']));
$router->get('/admin/holidays/edit', $adminMiddleware->handle([$holidayController, 'edit']));
$router->post('/admin/holidays/update', $adminMiddleware->handle([$holidayController, 'update']));
$router->post('/admin/holidays/toggle', $adminMiddleware->handle([$holidayController, 'toggleStatus']));
$router->get('/admin/work-calendar', $adminMiddleware->handle([$workCalendarController, 'index']));
$router->post('/admin/work-calendar/generate', $adminMiddleware->handle([$workCalendarController, 'generate']));
$router->get('/admin/work-locations', $adminMiddleware->handle([$workLocationController, 'index']));
$router->get('/admin/work-locations/create', $adminMiddleware->handle([$workLocationController, 'create']));
$router->post('/admin/work-locations/store', $adminMiddleware->handle([$workLocationController, 'store']));
$router->get('/admin/work-locations/edit', $adminMiddleware->handle([$workLocationController, 'edit']));
$router->post('/admin/work-locations/update', $adminMiddleware->handle([$workLocationController, 'update']));
$router->post('/admin/work-locations/toggle', $adminMiddleware->handle([$workLocationController, 'toggleStatus']));
$router->get('/admin/work-schedules', $adminMiddleware->handle([$workScheduleController, 'index']));
$router->get('/admin/work-schedules/create', $adminMiddleware->handle([$workScheduleController, 'create']));
$router->post('/admin/work-schedules/store', $adminMiddleware->handle([$workScheduleController, 'store']));
$router->get('/admin/work-schedules/edit', $adminMiddleware->handle([$workScheduleController, 'edit']));
$router->post('/admin/work-schedules/update', $adminMiddleware->handle([$workScheduleController, 'update']));
$router->post('/admin/work-schedules/toggle', $adminMiddleware->handle([$workScheduleController, 'toggleStatus']));
$router->post('/admin/work-schedules/assign', $adminMiddleware->handle([$workScheduleController, 'assign']));
