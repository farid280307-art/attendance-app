<?php

declare(strict_types=1);

namespace App\Middleware;

final class GuestMiddleware
{
    public function handle(callable $next): callable
    {
        return static function () use ($next) {
            if (\auth_check()) {
                \redirect('/dashboard');
            }

            return $next();
        };
    }
}
