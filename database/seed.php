<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/bootstrap.php';
require_once BASE_PATH . '/database/seeders/AdminSeeder.php';

use Database\Seeders\AdminSeeder;

try {
    $seeder = new AdminSeeder();
    $created = $seeder->run(db());

    echo $created
        ? '[SEEDED] Admin account created.' . PHP_EOL
        : '[SKIP] Admin account already exists.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, '[ERROR] Seeder gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
