<?php

declare(strict_types=1);

use App\Core\Router;

if (!isset($router) || !$router instanceof Router) {
    throw new LogicException('Router belum diinisialisasi.');
}

$appName = (string) ($GLOBALS['config']['app']['name'] ?? 'Attendance App');

$router->get('/', static function () use ($appName): void {
    view('home', [
        'pageTitle' => $appName,
        'heading' => $appName,
        'subtitle' => 'Sistem Absensi Karyawan',
        'showLoginButton' => true,
    ]);
});

$router->get('/login', static function () use ($appName): void {
    view('home', [
        'pageTitle' => 'Login - ' . $appName,
        'heading' => 'Login',
        'subtitle' => 'Halaman login akan tersedia pada phase authentication.',
        'showLoginButton' => false,
    ]);
});
