<?php

declare(strict_types=1);

$scheduleId = null;
$formAction = '/admin/work-schedules/store';
$submitLabel = 'Simpan Jadwal';
ob_start();
?>
<section class="management-heading mb-4"><div><p class="dashboard-eyebrow mb-1">Master Data</p><h1 class="dashboard-title">Tambah Jadwal Kerja</h1><p class="dashboard-date mb-0">Atur waktu, hari kerja, dan toleransi keterlambatan.</p></div></section>
<section class="dashboard-panel form-panel mx-auto"><?php require __DIR__ . '/_form.php'; ?></section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Tambah Jadwal Kerja - Attendance App';
$activeNavigation = 'work-schedules';
require BASE_PATH . '/app/Views/layouts/app.php';
