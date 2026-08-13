<?php

declare(strict_types=1);

$holidayId = null;
$formAction = '/admin/holidays/store';
$submitLabel = 'Simpan Hari Libur';
ob_start();
?>
<section class="management-heading mb-4"><div><p class="dashboard-eyebrow mb-1">Kalender</p><h1 class="dashboard-title">Tambah Hari Libur</h1><p class="dashboard-date mb-0">Tambahkan hari libur global atau perusahaan.</p></div></section>
<section class="dashboard-panel form-panel mx-auto"><?php require __DIR__ . '/_form.php'; ?></section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Tambah Hari Libur - Attendance App';
$activeNavigation = 'holidays';
require BASE_PATH . '/app/Views/layouts/app.php';
