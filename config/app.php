<?php

declare(strict_types=1);

$forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
$httpsEnabled = $forwardedProto === 'https'
    || (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off');

$scheme = $httpsEnabled ? 'https' : 'http';
$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
$host = trim(explode(',', (string) $host)[0]);
$basePath = '/attendance-app';

return [
    'name' => 'Attendance App',
    'timezone' => 'Asia/Jakarta',
    'base_url' => $scheme . '://' . $host . $basePath,
    'base_path' => $basePath,
];
