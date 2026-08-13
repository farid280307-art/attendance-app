<?php

declare(strict_types=1);

$locationId = null;
$formAction = '/admin/work-locations/store';
$submitLabel = 'Simpan Lokasi';
ob_start();
?>
<section class="management-heading mb-4">
    <div>
        <p class="dashboard-eyebrow mb-1">Master Data</p>
        <h1 class="dashboard-title">Tambah Lokasi Kerja</h1>
        <p class="dashboard-date mb-0">Masukkan titik pusat dan radius absensi secara manual.</p>
    </div>
</section>
<section class="dashboard-panel form-panel mx-auto">
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Tambah Lokasi Kerja - Attendance App';
$activeNavigation = 'work-locations';
require BASE_PATH . '/app/Views/layouts/app.php';
