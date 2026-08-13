<?php

declare(strict_types=1);

$monthError = is_string($monthError ?? null) ? $monthError : null;

ob_start();
?>
<section class="management-heading mb-4" aria-labelledby="admin-report-heading">
    <div>
        <p class="dashboard-eyebrow mb-1">Administrasi</p>
        <h1 class="dashboard-title" id="admin-report-heading">Rekap Absensi</h1>
        <p class="dashboard-date mb-0">Lihat rekap bulanan karyawan aktif maupun nonaktif.</p>
    </div>
</section>

<?php if ($monthError !== null): ?><div class="alert alert-warning" role="alert"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i><?= e($monthError) ?></div><?php endif; ?>

<section class="dashboard-panel report-filter-panel mb-4" aria-labelledby="admin-report-filter-title">
    <div class="panel-heading"><h2 class="panel-title mb-0" id="admin-report-filter-title">Filter Laporan</h2></div>
    <form class="report-filter-form is-admin" method="get" action="<?= e(url('/admin/reports/monthly')) ?>">
        <div><label class="form-label" for="month">Bulan</label><input class="form-control" id="month" name="month" type="month" min="2000-01" max="2100-12" value="<?= e($selectedMonth) ?>" required></div>
        <div><label class="form-label" for="employee_id">Karyawan</label><select class="form-select" id="employee_id" name="employee_id" required><option value="">Pilih karyawan</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['id']) ?>"<?= (int) ($selectedEmployee['id'] ?? 0) === (int) $employee['id'] ? ' selected' : '' ?>><?= e($employee['name']) ?> (<?= e($employee['employee_code']) ?>)<?= (int) $employee['is_active'] === 1 ? '' : ' — Nonaktif' ?></option><?php endforeach; ?></select></div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Tampilkan</button>
    </form>
</section>

<?php if ($selectedEmployee === null || $report === null): ?>
    <section class="dashboard-panel">
        <div class="empty-state"><span class="empty-state-icon" aria-hidden="true"><i class="bi bi-person-lines-fill"></i></span><h3>Pilih karyawan.</h3><p>Pilih bulan dan karyawan untuk menampilkan rekap absensi.</p></div>
    </section>
<?php else: ?>
    <section class="report-employee-card mb-4" aria-label="Karyawan terpilih">
        <span class="report-employee-avatar" aria-hidden="true"><i class="bi bi-person"></i></span>
        <div><small>Karyawan terpilih</small><strong><?= e($selectedEmployee['name']) ?></strong><span><?= e($selectedEmployee['employee_code']) ?></span></div>
        <span class="badge ms-sm-auto <?= (int) $selectedEmployee['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $selectedEmployee['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span>
    </section>

    <?php require BASE_PATH . '/app/Views/reports/_summary.php'; ?>
    <?php require BASE_PATH . '/app/Views/reports/_daily.php'; ?>
<?php endif; ?>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Rekap Absensi - Attendance App';
$activeNavigation = 'reports';
require BASE_PATH . '/app/Views/layouts/app.php';
