<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Validators\AuthValidator;

final class AuthController
{
    public function showLogin(): void
    {
        \view('auth.login', [
            'errors' => [],
            'oldUsername' => '',
            'alert' => \flash('error'),
            'success' => \flash('success'),
        ]);
    }

    public function login(): void
    {
        if (!\verify_csrf()) {
            \abort(419, 'errors.419');
            return;
        }

        $validation = (new AuthValidator())->validateLogin($_POST);
        $username = $validation['data']['username'];

        if (!$validation['valid']) {
            http_response_code(422);
            \view('auth.login', [
                'errors' => $validation['errors'],
                'oldUsername' => $username,
                'alert' => null,
                'success' => null,
            ]);
            return;
        }

        $user = (new User(\db()))->findByUsername($username);

        if ($user !== null && (int) $user['is_active'] !== 1) {
            http_response_code(422);
            \view('auth.login', [
                'errors' => [],
                'oldUsername' => $username,
                'alert' => 'Akun Anda sedang tidak aktif.',
                'success' => null,
            ]);
            return;
        }

        if (
            $user === null
            || !in_array($user['role'] ?? null, ['admin', 'employee'], true)
            || !password_verify($validation['data']['password'], (string) $user['password'])
        ) {
            http_response_code(422);
            \view('auth.login', [
                'errors' => [],
                'oldUsername' => $username,
                'alert' => 'Username atau password tidak valid.',
                'success' => null,
            ]);
            return;
        }

        session_regenerate_id(true);
        unset($_SESSION['_csrf_token']);
        $_SESSION['auth'] = [
            'user_id' => (int) $user['id'],
            'role' => (string) $user['role'],
            'logged_in_at' => time(),
        ];
        unset($GLOBALS['auth_user_cache']);

        \flash('success', 'Berhasil login.');
        \redirect('/dashboard');
    }

    public function logout(): void
    {
        if (!\verify_csrf()) {
            \abort(419, 'errors.419');
            return;
        }

        \auth_forget();
        unset($_SESSION['_csrf_token']);
        session_regenerate_id(true);

        \flash('success', 'Anda telah logout.');
        \redirect('/login');
    }
}
