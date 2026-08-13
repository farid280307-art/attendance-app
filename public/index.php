<?php

declare(strict_types=1);

use App\Core\Router;

ini_set('display_errors', '0');

try {
    require_once dirname(__DIR__) . '/bootstrap.php';

    $basePath = (string) ($GLOBALS['config']['app']['base_path'] ?? '');
    $router = new Router($basePath);

    require BASE_PATH . '/routes/web.php';

    $router->dispatch();
} catch (Throwable $exception) {
    error_log((string) $exception);

    if (!headers_sent()) {
        http_response_code(500);
    }

    if (function_exists('view')) {
        view('errors.500');
    } else {
        echo 'Terjadi kesalahan pada aplikasi.';
    }
}
