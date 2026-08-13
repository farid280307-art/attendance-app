<?php

declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses Ditolak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
    <main class="min-vh-100 d-flex align-items-center py-5">
        <div class="container">
            <section class="welcome-card mx-auto text-center">
                <p class="display-3 fw-bold text-danger mb-2">403</p>
                <h1 class="h2 mb-3">Akses Ditolak</h1>
                <p class="text-secondary mb-4">Anda tidak memiliki izin untuk membuka halaman ini.</p>
                <a class="btn btn-primary w-100" href="<?= e(url('/dashboard')) ?>">Kembali ke Dashboard</a>
            </section>
        </div>
    </main>
</body>
</html>
