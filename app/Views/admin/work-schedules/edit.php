<?php

declare(strict_types=1);

$formAction = '/admin/work-schedules/update';
$submitLabel = 'Simpan Perubahan';
ob_start();
?>
<section class="management-heading mb-4"><div><p class="dashboard-eyebrow mb-1">Master Data</p><h1 class="dashboard-title">Edit Jadwal Kerja</h1><p class="dashboard-date mb-0">Perbarui jam kerja, toleransi, atau status jadwal.</p></div></section>
<section class="dashboard-panel form-panel mx-auto"><?php require __DIR__ . '/_form.php'; ?></section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Edit Jadwal Kerja - Attendance App';
$activeNavigation = 'work-schedules';
require BASE_PATH . '/app/Views/layouts/app.php';
