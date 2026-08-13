<?php

declare(strict_types=1);

$errors = is_array($errors ?? null) ? $errors : [];
$oldUsername = is_string($oldUsername ?? null) ? $oldUsername : '';
$alert = is_string($alert ?? null) ? $alert : null;
$success = is_string($success ?? null) ? $success : null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Attendance App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
    <main class="min-vh-100 d-flex align-items-center py-5">
        <div class="container">
            <section class="welcome-card mx-auto" aria-labelledby="login-heading">
                <div class="text-center mb-4">
                    <p class="text-primary fw-semibold text-uppercase small mb-2">Attendance App</p>
                    <h1 id="login-heading" class="h2 fw-bold mb-2">Login</h1>
                    <p class="text-secondary mb-0">Masuk menggunakan akun Anda.</p>
                </div>

                <?php if ($alert !== null): ?>
                    <div class="alert alert-danger" role="alert"><?= e($alert) ?></div>
                <?php endif; ?>

                <?php if ($success !== null): ?>
                    <div class="alert alert-success" role="alert"><?= e($success) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= e(url('/login')) ?>" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input
                            type="text"
                            class="form-control<?= isset($errors['username']) ? ' is-invalid' : '' ?>"
                            id="username"
                            name="username"
                            value="<?= e($oldUsername) ?>"
                            maxlength="50"
                            autocomplete="username"
                            required
                            autofocus
                        >
                        <?php if (isset($errors['username'])): ?>
                            <div class="invalid-feedback"><?= e($errors['username']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input
                            type="password"
                            class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">Masuk</button>
                </form>
            </section>
        </div>
    </main>

    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
