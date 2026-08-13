<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

if (!isset($router) || !$router instanceof Router) {
    throw new LogicException('Router belum diinisialisasi.');
}

$appName = (string) ($GLOBALS['config']['app']['name'] ?? 'Attendance App');
$authController = new AuthController();
$dashboardController = new DashboardController();
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
