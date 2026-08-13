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
