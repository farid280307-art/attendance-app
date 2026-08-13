<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Attendance App';
$heading = $heading ?? 'Attendance App';
$subtitle = $subtitle ?? 'Sistem Absensi Karyawan';
$showLoginButton = $showLoginButton ?? true;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
    <main class="min-vh-100 d-flex align-items-center py-5">
        <div class="container">
            <section class="welcome-card mx-auto text-center" aria-labelledby="page-heading">
                <p class="text-primary fw-semibold text-uppercase small mb-2">Attendance App</p>
                <h1 id="page-heading" class="display-5 fw-bold mb-3"><?= e($heading) ?></h1>
                <p class="lead text-secondary mb-4"><?= e($subtitle) ?></p>

                <?php if ($showLoginButton): ?>
                    <a class="btn btn-primary btn-lg w-100" href="<?= e(url('/login')) ?>">Login</a>
                <?php else: ?>
                    <a class="btn btn-outline-primary w-100" href="<?= e(url('/')) ?>">Kembali ke Beranda</a>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
