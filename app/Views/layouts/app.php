<?php

declare(strict_types=1);

$pageTitle = is_string($pageTitle ?? null) ? $pageTitle : 'Attendance App';
$content = is_string($content ?? null) ? $content : '';
$isAdminLayout = ($user['role'] ?? null) === 'admin';
$activeNavigation = is_string($activeNavigation ?? null) ? $activeNavigation : 'dashboard';
$pageScripts = is_array($pageScripts ?? null) ? $pageScripts : [];
$navigationItems = $isAdminLayout
    ? [
        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'path' => '/dashboard', 'active' => $activeNavigation === 'dashboard'],
        ['label' => 'Absensi', 'icon' => 'bi-calendar-check', 'disabled' => true],
        ['label' => 'Pengajuan', 'icon' => 'bi-file-earmark-text', 'path' => '/admin/leave-requests', 'active' => $activeNavigation === 'admin-leave'],
        ['label' => 'Karyawan', 'icon' => 'bi-people', 'disabled' => true],
        ['label' => 'Lokasi Kerja', 'icon' => 'bi-geo-alt', 'path' => '/admin/work-locations', 'active' => $activeNavigation === 'work-locations'],
        ['label' => 'Jadwal Kerja', 'icon' => 'bi-clock', 'path' => '/admin/work-schedules', 'active' => $activeNavigation === 'work-schedules'],
        ['label' => 'Rekap', 'icon' => 'bi-bar-chart', 'disabled' => true],
    ]
    : [
        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'path' => '/dashboard', 'active' => $activeNavigation === 'dashboard'],
        ['label' => 'Absensi', 'icon' => 'bi-calendar-check', 'path' => '/attendance', 'active' => $activeNavigation === 'attendance'],
        ['label' => 'Pengajuan', 'icon' => 'bi-file-earmark-text', 'path' => '/leave', 'active' => $activeNavigation === 'leave'],
        ['label' => 'Rekap Saya', 'icon' => 'bi-bar-chart', 'disabled' => true],
    ];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2563eb">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="app-body">
    <div class="app-shell">
        <aside class="app-sidebar d-none d-lg-flex" aria-label="Navigasi utama">
            <a class="app-brand" href="<?= e(url('/dashboard')) ?>">
                <span class="app-brand-mark" aria-hidden="true"><i class="bi bi-check2-square"></i></span>
                <span>
                    <strong>Attendance</strong>
                    <small>Employee System</small>
                </span>
            </a>

            <?php require BASE_PATH . '/app/Views/partials/navigation.php'; ?>

            <div class="mt-auto">
                <?php require BASE_PATH . '/app/Views/partials/account-panel.php'; ?>
            </div>
        </aside>

        <header class="app-mobile-header d-lg-none">
            <a class="app-brand app-brand-mobile" href="<?= e(url('/dashboard')) ?>">
                <span class="app-brand-mark" aria-hidden="true"><i class="bi bi-check2-square"></i></span>
                <strong>Attendance</strong>
            </a>
            <button
                class="btn app-menu-button"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#mobileNavigation"
                aria-controls="mobileNavigation"
                aria-label="Buka navigasi"
            >
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
        </header>

        <div class="offcanvas offcanvas-start app-offcanvas d-lg-none" tabindex="-1" id="mobileNavigation" aria-labelledby="mobileNavigationLabel">
            <div class="offcanvas-header border-bottom">
                <div class="app-brand" id="mobileNavigationLabel">
                    <span class="app-brand-mark" aria-hidden="true"><i class="bi bi-check2-square"></i></span>
                    <span>
                        <strong>Attendance</strong>
                        <small>Employee System</small>
                    </span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup navigasi"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column">
                <?php require BASE_PATH . '/app/Views/partials/navigation.php'; ?>
                <div class="mt-auto pt-4">
                    <?php require BASE_PATH . '/app/Views/partials/account-panel.php'; ?>
                </div>
            </div>
        </div>

        <main class="app-main">
            <div class="app-content container-fluid">
                <?= $content ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
    <?php foreach ($pageScripts as $pageScript): ?>
        <?php if (is_string($pageScript) && $pageScript !== ''): ?>
            <script src="<?= e(asset($pageScript)) ?>"></script>
        <?php endif; ?>
    <?php endforeach; ?>
</body>
</html>
