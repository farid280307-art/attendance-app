<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/bootstrap.php';
require_once BASE_PATH . '/database/seeders/AdminSeeder.php';
require_once BASE_PATH . '/database/seeders/EmployeeSeeder.php';

use Database\Seeders\AdminSeeder;
use Database\Seeders\EmployeeSeeder;

try {
    $pdo = db();
    $adminCreated = (new AdminSeeder())->run($pdo);
    $isProduction = ($GLOBALS['config']['app']['environment'] ?? 'development') === 'production';
    $employeeCreated = $isProduction ? false : (new EmployeeSeeder())->run($pdo);

    echo $adminCreated
        ? '[SEEDED] Admin account created.' . PHP_EOL
        : '[SKIP] Admin account already exists.' . PHP_EOL;
    if ($isProduction) {
        echo '[SKIP] Development employee seeder disabled in production.' . PHP_EOL;
    } else {
        echo $employeeCreated
            ? '[SEEDED] Development employee account created.' . PHP_EOL
            : '[SKIP] Development employee account already exists.' . PHP_EOL;
    }
} catch (Throwable $exception) {
    fwrite(STDERR, '[ERROR] Seeder gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
