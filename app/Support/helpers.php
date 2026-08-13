<?php

declare(strict_types=1);

if (!function_exists('view')) {
    /**
     * Render a view from app/Views.
     *
     * @param array<string, mixed> $data
     */
    function view(string $view, array $data = []): void
    {
        $viewsDirectory = realpath(BASE_PATH . '/app/Views');
        $relativeView = str_replace('.', '/', trim($view, '/')) . '.php';
        $viewPath = realpath(BASE_PATH . '/app/Views/' . $relativeView);

        if (
            $viewsDirectory === false
            || $viewPath === false
            || strncmp($viewPath, $viewsDirectory . DIRECTORY_SEPARATOR, strlen($viewsDirectory) + 1) !== 0
            || !is_file($viewPath)
        ) {
            throw new RuntimeException('View tidak ditemukan: ' . $view);
        }

        extract($data, EXTR_SKIP);
        require $viewPath;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('json_response')) {
    /** @param array<string, mixed> $data */
    function json_response(array $data, int $status = 200): void
    {
        if ($status === 419 && !headers_sent()) {
            $protocol = (string) ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1');
            header($protocol . ' 419 Page Expired');
        } else {
            http_response_code($status);
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $baseUrl = rtrim((string) ($GLOBALS['config']['app']['base_url'] ?? ''), '/');
        $path = trim($path);

        if ($path === '' || $path === '/') {
            return $baseUrl;
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('e')) {
    /**
     * Escape a value for HTML output.
     *
     * @param mixed $value
     */
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(?string $token = null): bool
    {
        $sessionToken = $_SESSION['_csrf_token'] ?? null;
        $submittedToken = $token ?? ($_POST['_token'] ?? null);

        return is_string($sessionToken)
            && is_string($submittedToken)
            && hash_equals($sessionToken, $submittedToken);
    }
}

if (!function_exists('db')) {
    function db(): PDO
    {
        $connectionFactory = $GLOBALS['config']['database']['connection'] ?? null;

        if (!is_callable($connectionFactory)) {
            throw new RuntimeException('Konfigurasi koneksi database tidak valid.');
        }

        return $connectionFactory();
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }

        $storedMessage = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        if (isset($_SESSION['_flash']) && $_SESSION['_flash'] === []) {
            unset($_SESSION['_flash']);
        }

        return is_string($storedMessage) ? $storedMessage : null;
    }
}

if (!function_exists('auth_forget')) {
    function auth_forget(): void
    {
        unset($_SESSION['auth']);
        $GLOBALS['auth_user_cache'] = false;
    }
}

if (!function_exists('auth_user')) {
    /**
     * @return array<string, mixed>|null
     */
    function auth_user(): ?array
    {
        if (array_key_exists('auth_user_cache', $GLOBALS)) {
            $cachedUser = $GLOBALS['auth_user_cache'];
            return is_array($cachedUser) ? $cachedUser : null;
        }

        $userId = $_SESSION['auth']['user_id'] ?? null;
        $sessionRole = $_SESSION['auth']['role'] ?? null;

        if (!is_int($userId) || $userId < 1 || !in_array($sessionRole, ['admin', 'employee'], true)) {
            auth_forget();
            return null;
        }

        $user = (new App\Models\User(db()))->findById($userId);

        if (
            $user === null
            || (int) ($user['is_active'] ?? 0) !== 1
            || !in_array($user['role'] ?? null, ['admin', 'employee'], true)
            || $user['role'] !== $sessionRole
        ) {
            auth_forget();
            return null;
        }

        $GLOBALS['auth_user_cache'] = $user;
        return $user;
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool
    {
        return auth_user() !== null;
    }
}

if (!function_exists('guest')) {
    function guest(): bool
    {
        return !auth_check();
    }
}

if (!function_exists('auth_id')) {
    function auth_id(): ?int
    {
        $user = auth_user();
        return $user === null ? null : (int) $user['id'];
    }
}

if (!function_exists('auth_role')) {
    function auth_role(): ?string
    {
        $user = auth_user();
        return $user === null ? null : (string) $user['role'];
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return auth_role() === 'admin';
    }
}

if (!function_exists('abort')) {
    /**
     * @param array<string, mixed> $data
     */
    function abort(int $status, string $viewName, array $data = []): void
    {
        if ($status === 419 && !headers_sent()) {
            $protocol = (string) ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1');
            header($protocol . ' 419 Page Expired');
        } else {
            http_response_code($status);
        }

        view($viewName, $data);
    }
}

if (!function_exists('positive_int')) {
    function positive_int(mixed $value): ?int
    {
        if (!is_int($value) && !is_string($value)) {
            return null;
        }

        $normalized = is_string($value) ? trim($value) : (string) $value;

        if ($normalized === '' || preg_match('/^[1-9][0-9]*$/', $normalized) !== 1) {
            return null;
        }

        $validated = filter_var($normalized, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $validated === false ? null : (int) $validated;
    }
}

if (!function_exists('indonesian_date')) {
    function indonesian_date(DateTimeInterface $date): string
    {
        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return sprintf(
            '%s, %d %s %s',
            $days[(int) $date->format('N')],
            (int) $date->format('j'),
            $months[(int) $date->format('n')],
            $date->format('Y')
        );
    }
}

if (!function_exists('indonesian_month_year')) {
    function indonesian_month_year(DateTimeInterface $date): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $months[(int) $date->format('n')] . ' ' . $date->format('Y');
    }
}

if (!function_exists('indonesian_short_date')) {
    function indonesian_short_date(?string $date): string
    {
        if ($date === null || $date === '') {
            return '--';
        }

        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsedDate === false) {
            return '--';
        }

        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        return sprintf(
            '%d %s %s',
            (int) $parsedDate->format('j'),
            $months[(int) $parsedDate->format('n')],
            $parsedDate->format('Y')
        );
    }
}

if (!function_exists('indonesian_date_range')) {
    function indonesian_date_range(?string $startDate, ?string $endDate): string
    {
        $start = indonesian_short_date($startDate);
        $end = indonesian_short_date($endDate);

        return $start === $end ? $start : $start . ' – ' . $end;
    }
}

if (!function_exists('format_attendance_time')) {
    function format_attendance_time(?string $dateTime): string
    {
        if ($dateTime === null || $dateTime === '') {
            return '--';
        }

        $parsedDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTime);

        return $parsedDate === false ? '--' : $parsedDate->format('H:i');
    }
}

if (!function_exists('time_greeting')) {
    function time_greeting(DateTimeInterface $date): string
    {
        $hour = (int) $date->format('G');

        if ($hour >= 5 && $hour <= 10) {
            return 'Selamat pagi';
        }

        if ($hour >= 11 && $hour <= 14) {
            return 'Selamat siang';
        }

        if ($hour >= 15 && $hour <= 17) {
            return 'Selamat sore';
        }

        return 'Selamat malam';
    }
}

if (!function_exists('attendance_status_label')) {
    function attendance_status_label(?string $status): string
    {
        return $status === 'late' ? 'Terlambat' : 'Hadir';
    }
}

if (!function_exists('attendance_status_class')) {
    function attendance_status_class(?string $status): string
    {
        return $status === 'late' ? 'text-bg-warning' : 'text-bg-success';
    }
}

if (!function_exists('leave_type_label')) {
    function leave_type_label(?string $type): string
    {
        return [
            'leave' => 'Cuti',
            'sick' => 'Sakit',
            'permission' => 'Izin',
        ][$type] ?? 'Pengajuan';
    }
}

if (!function_exists('leave_status_label')) {
function leave_status_label(?string $status): string
    {
        return [
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ][$status] ?? 'Tidak diketahui';
    }
}

if (!function_exists('format_app_datetime')) {
    function format_app_datetime(?string $dateTime): string
    {
        if ($dateTime === null || $dateTime === '') {
            return '--';
        }

        $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTime);

        if ($parsed === false) {
            return '--';
        }

        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        return sprintf(
            '%d %s %s, %s WIB',
            (int) $parsed->format('j'),
            $months[(int) $parsed->format('n')],
            $parsed->format('Y'),
            $parsed->format('H:i')
        );
    }
}

if (!function_exists('text_excerpt')) {
    function text_excerpt(?string $value, int $maxLength = 90): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return '--';
        }

        $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);

        if ($length <= $maxLength) {
            return $text;
        }

        $excerpt = function_exists('mb_substr')
            ? mb_substr($text, 0, $maxLength - 1, 'UTF-8')
            : substr($text, 0, $maxLength - 1);

        return rtrim($excerpt) . '…';
    }
}

if (!function_exists('leave_status_class')) {
    function leave_status_class(?string $status): string
    {
        return [
            'pending' => 'text-bg-warning',
            'approved' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
        ][$status] ?? 'text-bg-secondary';
    }
}

if (!function_exists('report_status_class')) {
    function report_status_class(?string $status): string
    {
        return [
            'present' => 'text-bg-success',
            'late' => 'text-bg-warning',
            'leave' => 'text-bg-primary',
            'sick' => 'text-bg-danger',
            'permission' => 'text-bg-info',
            'no_record' => 'text-bg-secondary',
            'future' => 'report-future-badge',
        ][$status] ?? 'text-bg-secondary';
    }
}

if (!function_exists('report_day_description')) {
    /** @param array<string, mixed> $day */
    function report_day_description(array $day): string
    {
        if (($day['status'] ?? null) === 'late') {
            return 'Terlambat ' . (int) ($day['late_minutes'] ?? 0) . ' menit';
        }

        return (string) ($day['status_label'] ?? 'Tidak Ada Data');
    }
}
