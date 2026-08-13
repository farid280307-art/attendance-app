<?php

declare(strict_types=1);

$success = is_string($success ?? null) ? $success : null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin - Attendance App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
    <main class="min-vh-100 d-flex align-items-center py-5">
        <div class="container">
            <section class="welcome-card mx-auto text-center" aria-labelledby="dashboard-heading">
                <?php if ($success !== null): ?>
                    <div class="alert alert-success text-start" role="alert"><?= e($success) ?></div>
                <?php endif; ?>

                <p class="text-primary fw-semibold text-uppercase small mb-2">Attendance App</p>
                <h1 id="dashboard-heading" class="h2 fw-bold mb-3">Dashboard Admin</h1>
                <p class="text-secondary mb-4">Selamat datang, <?= e($user['name']) ?></p>

                <form method="POST" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger w-100">Logout</button>
                </form>
            </section>
        </div>
    </main>

    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
