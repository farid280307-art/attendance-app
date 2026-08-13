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
