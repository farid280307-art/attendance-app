<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

$GLOBALS['config'] = [
    'app' => require BASE_PATH . '/config/app.php',
    'database' => require BASE_PATH . '/config/database.php',
];

$appConfig = $GLOBALS['config']['app'];
$debug = ($appConfig['debug'] ?? false) === true;
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

$timezone = (string) ($appConfig['timezone'] ?? 'Asia/Jakarta');

if (!date_default_timezone_set($timezone)) {
    throw new RuntimeException('Timezone aplikasi tidak valid.');
}

$isWebRequest = PHP_SAPI !== 'cli';

if ($isWebRequest && !headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: camera=(self), geolocation=(self)');
    header('Cache-Control: private, no-store');
}

if ($isWebRequest && session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $sessionConfig = is_array($appConfig['session'] ?? null) ? $appConfig['session'] : [];
    $cookiePath = (string) ($appConfig['base_path'] ?? '');
    $cookiePath = $cookiePath === '' ? '/' : rtrim($cookiePath, '/');

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_trans_sid', '0');
    session_name((string) ($sessionConfig['name'] ?? 'attendance_app_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath,
        'secure' => ($sessionConfig['secure_cookie'] ?? false) === true,
        'httponly' => true,
        'samesite' => (string) ($sessionConfig['same_site'] ?? 'Lax'),
    ]);
    session_start();
}

require_once BASE_PATH . '/app/Support/helpers.php';
