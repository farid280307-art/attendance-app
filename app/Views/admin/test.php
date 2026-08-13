<?php

declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Test - Attendance App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
    <main class="min-vh-100 d-flex align-items-center py-5">
        <div class="container">
            <section class="welcome-card mx-auto text-center">
                <h1 class="h2 fw-bold mb-3">Admin access granted.</h1>
                <a class="btn btn-primary w-100" href="<?= e(url('/dashboard')) ?>">Kembali ke Dashboard</a>
            </section>
        </div>
    </main>
</body>
</html>
