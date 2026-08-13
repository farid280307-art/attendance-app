<?php

declare(strict_types=1);

$monthError = is_string($monthError ?? null) ? $monthError : null;

ob_start();
?>
<section class="management-heading mb-4" aria-labelledby="monthly-report-heading">
    <div>
        <p class="dashboard-eyebrow mb-1">Karyawan</p>
        <h1 class="dashboard-title" id="monthly-report-heading">Rekap Saya</h1>
        <p class="dashboard-date mb-0">Ringkasan attendance dan pengajuan yang telah disetujui per tanggal.</p>
    </div>
</section>

<?php if ($monthError !== null): ?><div class="alert alert-warning" role="alert"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i><?= e($monthError) ?></div><?php endif; ?>

<section class="dashboard-panel report-filter-panel mb-4" aria-labelledby="report-filter-title">
    <div class="panel-heading"><h2 class="panel-title mb-0" id="report-filter-title">Filter Laporan</h2></div>
    <form class="report-filter-form" method="get" action="<?= e(url('/reports/monthly')) ?>">
        <div><label class="form-label" for="month">Bulan</label><input class="form-control" id="month" name="month" type="month" min="2000-01" max="2100-12" value="<?= e($selectedMonth) ?>" required></div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Tampilkan</button>
    </form>
</section>

<?php require __DIR__ . '/_summary.php'; ?>
<?php require __DIR__ . '/_daily.php'; ?>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Rekap Saya - Attendance App';
$activeNavigation = 'reports';
require BASE_PATH . '/app/Views/layouts/app.php';
