<?php

declare(strict_types=1);

namespace App\Controllers;

final class DashboardController
{
    public function index(): void
    {
        $user = \auth_user();

        if ($user === null) {
            \redirect('/login');
        }

        $viewName = $user['role'] === 'admin'
            ? 'admin.dashboard'
            : 'employee.dashboard';

        \view($viewName, [
            'user' => $user,
            'success' => \flash('success'),
        ]);
    }

    public function adminTest(): void
    {
        \view('admin.test');
    }
}
