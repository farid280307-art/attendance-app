<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Router;
use App\Services\ImageService;
use App\Services\LeaveAttachmentService;
use App\Services\LocationService;
use App\Validators\AdminAttendanceFilterValidator;
use App\Validators\LeaveValidator;
use App\Validators\LocationValidator;

$failed = 0;
$check = static function (bool $condition, string $label) use (&$failed): void {
    echo ($condition ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;

    if (!$condition) {
        $failed++;
    }
};

$appConfig = $GLOBALS['config']['app'] ?? [];
$databaseConfig = $GLOBALS['config']['database'] ?? [];
$check(defined('BASE_PATH') && BASE_PATH === dirname(__DIR__), 'Bootstrap dan BASE_PATH');
$check(in_array($appConfig['environment'] ?? null, ['development', 'production'], true), 'APP_ENV tervalidasi');
$check(filter_var($appConfig['base_url'] ?? null, FILTER_VALIDATE_URL) !== false, 'APP_URL valid');
$check(($appConfig['timezone'] ?? null) === 'Asia/Jakarta' && date_default_timezone_get() === 'Asia/Jakarta', 'Timezone Asia/Jakarta');
$check(is_callable($databaseConfig['connection'] ?? null), 'Lazy database connection tersedia');
$check(ini_size_in_bytes('5M') === 5 * 1024 * 1024, 'Parser batas upload');

$locations = new LocationService();
$check(abs($locations->calculateDistanceMeters(-6.2, 106.8, -6.2, 106.8)) < 0.001, 'Haversine titik identik');
$knownDistance = $locations->calculateDistanceMeters(0.0, 0.0, 0.0, 0.001);
$check($knownDistance > 110.0 && $knownDistance < 112.0, 'Haversine jarak referensi');

$locationValidation = (new LocationValidator())->validate([
    'latitude' => '-6.2',
    'longitude' => '106.8',
    'accuracy' => '12.5',
    'user_id' => '999',
    'status' => 'present',
    'distance' => '0',
]);
$check($locationValidation['valid'] === true, 'Validator lokasi valid');
$check(
    array_keys($locationValidation['data']) === ['latitude', 'longitude', 'accuracy'],
    'Validator lokasi mengabaikan field keputusan dari client'
);
$check((new LocationValidator())->validate(['latitude' => 'nan'])['valid'] === false, 'Validator lokasi menolak input non-finite');

$leaveValidation = (new LeaveValidator())->validateCreate([
    'type' => 'leave',
    'start_date' => '2026-08-14',
    'end_date' => '2026-08-13',
    'reason' => 'Pengujian validator',
]);
$check($leaveValidation['valid'] === false && isset($leaveValidation['errors']['end_date']), 'Validator rentang pengajuan');

$filterValidation = (new AdminAttendanceFilterValidator())->validate([
    'date' => '2026-02-31',
    'employee_id' => '../../1',
    'status' => 'password',
    'page' => '0',
]);
$check(
    $filterValidation['filters'] === ['date' => null, 'employee_id' => null, 'status' => null]
        && $filterValidation['page'] === 1,
    'Validator filter admin menolak input tampered'
);

$check((new ImageService())->resolveAttendanceSelfie('../../file.jpg') === null, 'Resolver selfie menolak traversal');
$check((new LeaveAttachmentService())->resolve('leave/../../file.pdf') === null, 'Resolver lampiran menolak traversal');

try {
    $router = new Router((string) ($appConfig['base_path'] ?? ''));
    require BASE_PATH . '/routes/web.php';
    $check(true, 'Definisi route berhasil dimuat');
} catch (Throwable $exception) {
    $check(false, 'Definisi route berhasil dimuat');
}

echo PHP_EOL . ($failed === 0 ? 'Smoke test complete.' : sprintf('%d smoke test gagal.', $failed)) . PHP_EOL;
exit($failed === 0 ? 0 : 1);
