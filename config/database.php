<?php

declare(strict_types=1);

$environmentValue = static function (string $key, string $default): string {
    $value = getenv($key);

    return $value === false ? $default : $value;
};

$host = trim($environmentValue('DB_HOST', '127.0.0.1'));
$port = filter_var($environmentValue('DB_PORT', '3306'), FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 65535],
]);
$database = trim($environmentValue('DB_NAME', 'attendance_app'));
$username = trim($environmentValue('DB_USER', 'root'));

if ($host === '' || str_contains($host, ';')) {
    throw new RuntimeException('DB_HOST tidak valid.');
}

if ($port === false) {
    throw new RuntimeException('DB_PORT harus berupa port TCP yang valid.');
}

if (preg_match('/^[A-Za-z0-9_-]+$/D', $database) !== 1) {
    throw new RuntimeException('DB_NAME hanya boleh berisi huruf, angka, garis bawah, dan tanda hubung.');
}

if ($username === '') {
    throw new RuntimeException('DB_USER tidak boleh kosong.');
}

$config = [
    'driver' => 'mysql',
    'host' => $host,
    'port' => (int) $port,
    'database' => $database,
    'username' => $username,
    'password' => $environmentValue('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

// Koneksi baru dibuat saat factory ini benar-benar dipanggil.
$config['connection'] = static function () use ($config): PDO {
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $dsn = sprintf(
        '%s:host=%s;port=%d;dbname=%s;charset=%s',
        $config['driver'],
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    $connection = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        $config['options']
    );
    $connection->exec("SET time_zone = '+07:00'");

    return $connection;
};

return $config;
