<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/bootstrap.php';

$migrationDirectory = BASE_PATH . '/database/migrations';

try {
    $pdo = db();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `migrations` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration` VARCHAR(255) NOT NULL,
            `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_migrations_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $migrationFiles = glob($migrationDirectory . '/*.sql');

    if ($migrationFiles === false) {
        throw new RuntimeException('Tidak dapat membaca direktori migration.');
    }

    sort($migrationFiles, SORT_NATURAL | SORT_FLAG_CASE);

    $executedMigrations = $pdo
        ->query('SELECT `migration` FROM `migrations`')
        ->fetchAll(PDO::FETCH_COLUMN);
    $executedLookup = array_fill_keys($executedMigrations, true);
    $recordMigration = $pdo->prepare(
        'INSERT INTO `migrations` (`migration`) VALUES (:migration)'
    );
    $supportsTransactionalDdl = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql';

    echo 'Migration started...' . PHP_EOL . PHP_EOL;

    foreach ($migrationFiles as $migrationFile) {
        $migrationName = basename($migrationFile);

        if (isset($executedLookup[$migrationName])) {
            echo '[SKIP] ' . $migrationName . PHP_EOL;
            continue;
        }

        $sql = file_get_contents($migrationFile);

        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('File migration kosong atau tidak dapat dibaca: ' . $migrationName);
        }

        echo '[RUN] ' . $migrationName . PHP_EOL;

        if ($supportsTransactionalDdl) {
            $pdo->beginTransaction();
        }

        try {
            $pdo->exec($sql);
            $recordMigration->execute(['migration' => $migrationName]);

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw new RuntimeException(
                sprintf('Migration gagal [%s]: %s', $migrationName, $exception->getMessage()),
                0,
                $exception
            );
        }

        echo '[DONE] ' . $migrationName . PHP_EOL . PHP_EOL;
    }

    echo 'Migration complete.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, '[ERROR] ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
