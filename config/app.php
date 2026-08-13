<?php

declare(strict_types=1);

$environmentValue = getenv('APP_ENV');
$environment = strtolower(trim($environmentValue === false ? 'development' : $environmentValue));

if (!in_array($environment, ['development', 'production'], true)) {
    throw new RuntimeException('APP_ENV harus bernilai development atau production.');
}

$booleanEnvironment = static function (string $key, bool $default): bool {
    $value = getenv($key);
    if ($value === false) return $default;
    $parsed = filter_var(trim($value), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($parsed === null) throw new RuntimeException($key . ' harus berupa nilai boolean.');
    return $parsed;
};

$urlValue = getenv('APP_URL');

// In development, derive the public URL from the current request when APP_URL is not explicitly set.
// This keeps redirects working through localhost, LAN IPs, and HTTPS tunnels such as ngrok.
if ($urlValue === false || trim($urlValue) === '') {
    $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    $httpsEnabled = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || $forwardedProto === 'https';
    $scheme = $httpsEnabled ? 'https' : 'http';

    $forwardedHost = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''))[0]);
    $requestHost = $forwardedHost !== '' ? $forwardedHost : (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $requestHost = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $requestHost) ?: 'localhost';

    $baseUrl = $scheme . '://' . $requestHost . '/attendance-app';
} else {
    $baseUrl = rtrim(trim($urlValue), '/');
}

$urlParts = parse_url($baseUrl);

if (
    !is_array($urlParts)
    || !in_array($urlParts['scheme'] ?? null, ['http', 'https'], true)
    || !is_string($urlParts['host'] ?? null)
    || $urlParts['host'] === ''
    || isset($urlParts['user'])
    || isset($urlParts['pass'])
    || isset($urlParts['query'])
    || isset($urlParts['fragment'])
) {
    throw new RuntimeException('APP_URL harus berupa URL HTTP/HTTPS tanpa query, fragment, atau credential.');
}

$basePath = rtrim((string) ($urlParts['path'] ?? ''), '/');
$basePath = $basePath === '/' ? '' : $basePath;
$accuracyValue = getenv('MAX_LOCATION_ACCURACY_METERS');
$maximumAccuracy = $accuracyValue === false ? 100.0 : filter_var(trim($accuracyValue), FILTER_VALIDATE_FLOAT);

if ($maximumAccuracy === false || !is_finite((float) $maximumAccuracy) || $maximumAccuracy < 1 || $maximumAccuracy > 10000) {
    throw new RuntimeException('MAX_LOCATION_ACCURACY_METERS harus berada antara 1 dan 10000.');
}

$debug = $booleanEnvironment('APP_DEBUG', $environment === 'development');
$secureCookie = $booleanEnvironment('SESSION_SECURE_COOKIE', ($urlParts['scheme'] ?? 'http') === 'https');

if ($environment === 'production' && (($urlParts['scheme'] ?? null) !== 'https' || !$secureCookie || $debug)) {
    throw new RuntimeException('Production mewajibkan APP_URL HTTPS, APP_DEBUG=false, dan SESSION_SECURE_COOKIE=true.');
}

return [
    'name' => 'Attendance App',
    'version' => '1.0.0',
    'environment' => $environment,
    'debug' => $debug,
    'timezone' => 'Asia/Jakarta',
    'base_url' => $baseUrl,
    'base_path' => $basePath,
    'max_location_accuracy_meters' => (float) $maximumAccuracy,
    'session' => [
        'name' => 'attendance_app_session',
        'secure_cookie' => $secureCookie,
        'same_site' => 'Lax',
    ],
];
