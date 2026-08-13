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
    $employeeCreated = (new EmployeeSeeder())->run($pdo);

    echo $adminCreated
        ? '[SEEDED] Admin account created.' . PHP_EOL
        : '[SKIP] Admin account already exists.' . PHP_EOL;
    echo $employeeCreated
        ? '[SEEDED] Development employee account created.' . PHP_EOL
        : '[SKIP] Development employee account already exists.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, '[ERROR] Seeder gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
