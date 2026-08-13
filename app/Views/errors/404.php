<?php

declare(strict_types=1);

$requestedPath = $requestedPath ?? '/';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Halaman Tidak Ditemukan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
    <main class="min-vh-100 d-flex align-items-center py-5">
        <div class="container">
            <section class="welcome-card mx-auto text-center" aria-labelledby="error-heading">
                <p class="display-3 fw-bold text-primary mb-2">404</p>
                <h1 id="error-heading" class="h2 mb-3">Halaman Tidak Ditemukan</h1>
                <p class="text-secondary mb-4">Halaman yang Anda cari tidak tersedia.</p>
                <a class="btn btn-primary w-100" href="<?= e(url('/')) ?>">Kembali ke Beranda</a>
            </section>
        </div>
    </main>

    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
