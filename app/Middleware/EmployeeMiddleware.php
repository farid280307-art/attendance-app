<?php

declare(strict_types=1);

namespace App\Middleware;

final class EmployeeMiddleware
{
    public function handle(callable $next): callable
    {
        return static function () use ($next) {
            if (!\auth_check()) {
                \flash('error', 'Silakan login untuk melanjutkan.');
                \redirect('/login');
            }

            if (\auth_role() !== 'employee') {
                \abort(403, 'errors.403');
                return null;
            }

            return $next();
        };
    }
}
