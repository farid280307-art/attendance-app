<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/bootstrap.php';

$confirmation = $argv[1] ?? '';

if ($confirmation !== '--confirm=BERSIHKAN') {
    fwrite(STDERR, "Perintah ini akan menghapus data operasional. Jalankan dengan:\n");
    fwrite(STDERR, "php database/prepare-handoff.php --confirm=BERSIHKAN\n");
    exit(1);
}

/** @return string */
function handoffPassword(string $environmentKey, string $prefix): string
{
    $value = getenv($environmentKey);
    $password = $value === false || trim($value) === ''
        ? $prefix . '-' . bin2hex(random_bytes(8))
        : trim($value);

    if (strlen($password) < 12) {
        throw new RuntimeException($environmentKey . ' minimal 12 karakter.');
    }

    return $password;
}

function cleanRuntimeDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $items = scandir($directory);

    if ($items === false) {
        throw new RuntimeException('Direktori runtime tidak dapat dibaca: ' . $directory);
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path) && !is_link($path)) {
            cleanRuntimeDirectory($path);

            if (!rmdir($path)) {
                throw new RuntimeException('Gagal menghapus direktori runtime: ' . $path);
            }

            continue;
        }

        if (!unlink($path)) {
            throw new RuntimeException('Gagal menghapus file runtime: ' . $path);
        }
    }
}

try {
    $pdo = db();

    $locationStatement = $pdo->prepare(
        'SELECT `id`, `name`, `latitude`, `longitude`, `radius_meters`, `is_active`
         FROM `work_locations`
         WHERE LOWER(`name`) LIKE :name
         ORDER BY `id` ASC'
    );
    $locationStatement->execute(['name' => '%smkn manonjaya%']);
    $matchingLocations = $locationStatement->fetchAll(PDO::FETCH_ASSOC);

    if (count($matchingLocations) === 0) {
        throw new RuntimeException(
            'Lokasi yang mengandung nama "SMKN Manonjaya" tidak ditemukan. Proses dibatalkan agar lokasi penting tidak ikut terhapus.'
        );
    }

    if (count($matchingLocations) > 1) {
        $matches = array_map(
            static fn (array $row): string => sprintf('#%d %s', (int) $row['id'], (string) $row['name']),
            $matchingLocations
        );

        throw new RuntimeException(
            'Ditemukan lebih dari satu lokasi SMKN Manonjaya: ' . implode(', ', $matches) . '. Rapikan terlebih dahulu lalu jalankan ulang.'
        );
    }

    $keptLocation = $matchingLocations[0];
    $keptLocationId = (int) $keptLocation['id'];

    $adminPassword = handoffPassword('HANDOFF_ADMIN_PASSWORD', 'Adm');
    $juliantiPassword = handoffPassword('HANDOFF_JULIANTI_PASSWORD', 'Jul');
    $adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $juliantiHash = password_hash($juliantiPassword, PASSWORD_DEFAULT);

    if ($adminHash === false || $juliantiHash === false) {
        throw new RuntimeException('Gagal membuat hash password akun handoff.');
    }

    $pdo->beginTransaction();

    // Hapus seluruh data operasional / demo terlebih dahulu.
    $pdo->exec('DELETE FROM `employee_work_calendars`');
    $pdo->exec('DELETE FROM `leave_requests`');
    $pdo->exec('DELETE FROM `attendances`');
    $pdo->exec('DELETE FROM `activity_logs`');

    // Lepaskan relasi jadwal sebelum master jadwal dibersihkan.
    $pdo->exec('UPDATE `users` SET `work_schedule_id` = NULL');
    $pdo->exec('DELETE FROM `users`');
    $pdo->exec('DELETE FROM `work_schedule_days`');
    $pdo->exec('DELETE FROM `work_schedules`');
    $pdo->exec('DELETE FROM `holidays`');

    // Pertahankan hanya lokasi SMKN Manonjaya.
    $deleteLocations = $pdo->prepare('DELETE FROM `work_locations` WHERE `id` <> :keep_id');
    $deleteLocations->execute(['keep_id' => $keptLocationId]);

    $insertUser = $pdo->prepare(
        'INSERT INTO `users`
            (`name`, `employee_code`, `username`, `password`, `role`, `work_schedule_id`, `is_active`)
         VALUES
            (:name, :employee_code, :username, :password, :role, NULL, 1)'
    );

    $insertUser->execute([
        'name' => 'Administrator',
        'employee_code' => 'ADMIN001',
        'username' => 'admin',
        'password' => $adminHash,
        'role' => 'admin',
    ]);

    $insertUser->execute([
        'name' => 'Julianti',
        'employee_code' => 'EMP001',
        'username' => 'julianti',
        'password' => $juliantiHash,
        'role' => 'employee',
    ]);

    $pdo->commit();

    // Bersihkan bukti selfie/lampiran lama setelah transaksi database sukses.
    cleanRuntimeDirectory(BASE_PATH . '/storage/attendance');
    cleanRuntimeDirectory(BASE_PATH . '/storage/leave');

    echo '[OK] Database handoff berhasil dibersihkan.' . PHP_EOL;
    echo '[KEEP] Lokasi: #' . $keptLocationId . ' ' . $keptLocation['name'] . PHP_EOL;
    echo '[ACCOUNT] Admin    | username: admin    | password: ' . $adminPassword . PHP_EOL;
    echo '[ACCOUNT] Julianti | username: julianti | password: ' . $juliantiPassword . PHP_EOL;
    echo '[INFO] Jadwal kerja, hari libur, kalender, absensi, pengajuan, activity log, dan file bukti lama telah dibersihkan.' . PHP_EOL;
    echo '[NEXT] Login sebagai admin, buat/atur jadwal kerja, assign ke Julianti, lalu generate kalender kerja sebelum absensi pertama.' . PHP_EOL;
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, '[ERROR] Persiapan handoff gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
