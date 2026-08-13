<?php

declare(strict_types=1);

$httpsEnabled = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
$scheme = $httpsEnabled ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = '/attendance-app';

return [
    'name' => 'Attendance App',
    'timezone' => 'Asia/Jakarta',
    'base_url' => $scheme . '://' . $host . $basePath,
    'base_path' => $basePath,
    'max_location_accuracy_meters' => 100.0,
];
