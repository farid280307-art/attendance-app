<?php

declare(strict_types=1);

namespace App\Core;

use LogicException;

class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $normalizedBasePath = '/' . trim($basePath, '/');
        $this->basePath = $normalizedBasePath === '/' ? '' : $normalizedBasePath;
    }

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $requestPath = parse_url($requestUri, PHP_URL_PATH);
        $path = is_string($requestPath) ? $requestPath : '/';
        $path = $this->removeBasePath($path);

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            \view('errors.404', ['requestedPath' => $path]);
            return;
        }

        $response = $handler();

        if (is_string($response)) {
            echo $response;
        }
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $path = $this->normalizeRoutePath($path);

        if (isset($this->routes[$method][$path])) {
            throw new LogicException(sprintf('Route %s %s sudah terdaftar.', $method, $path));
        }

        $this->routes[$method][$path] = $handler;
    }

    private function normalizeRoutePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        return '/' . trim($path, '/');
    }

    private function removeBasePath(string $path): string
    {
        if ($this->basePath === '') {
            return $path === '' ? '/' : $path;
        }

        if ($path === $this->basePath) {
            return '/';
        }

        $prefix = $this->basePath . '/';

        if (strncmp($path, $prefix, strlen($prefix)) === 0) {
            $path = substr($path, strlen($this->basePath));
        }

        return $path === '' ? '/' : $path;
    }
}
