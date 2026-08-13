<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\WorkLocationController;
use App\Controllers\WorkScheduleController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

if (!isset($router) || !$router instanceof Router) {
    throw new LogicException('Router belum diinisialisasi.');
}

$appName = (string) ($GLOBALS['config']['app']['name'] ?? 'Attendance App');
$authController = new AuthController();
$dashboardController = new DashboardController();
$workLocationController = new WorkLocationController();
$workScheduleController = new WorkScheduleController();
$authMiddleware = new AuthMiddleware();
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
$router->get('/admin/test', $adminMiddleware->handle([$dashboardController, 'adminTest']));
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
