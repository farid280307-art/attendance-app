<?php

declare(strict_types=1);

$queryForPage = static function (int $page) use ($filters): string {
    $query = array_filter([
        'date' => $filters['date'],
        'employee_id' => $filters['employee_id'],
        'status' => $filters['status'],
        'page' => $page > 1 ? $page : null,
    ], static fn (mixed $value): bool => $value !== null && $value !== '');

    return '/admin/attendances' . ($query === [] ? '' : '?' . http_build_query($query));
};
ob_start();
?>
<section class="management-heading mb-4" aria-labelledby="admin-attendance-title"><div><p class="dashboard-eyebrow mb-1">Administrasi</p><h1 class="dashboard-title" id="admin-attendance-title">Data Absensi</h1><p class="dashboard-date mb-0">Lihat catatan kehadiran dan bukti verifikasi karyawan.</p></div></section>

<?php if ($filterErrors !== []): ?><div class="alert alert-warning" role="alert"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i><?= e(implode(' ', array_values($filterErrors))) ?></div><?php endif; ?>

<section class="dashboard-panel mb-4" aria-labelledby="attendance-filter-title">
    <div class="panel-heading"><h2 class="panel-title mb-0" id="attendance-filter-title">Filter Absensi</h2></div>
    <form class="attendance-admin-filter" method="get" action="<?= e(url('/admin/attendances')) ?>">
        <div><label class="form-label" for="date">Tanggal</label><input class="form-control" id="date" name="date" type="date" value="<?= e($filters['date'] ?? '') ?>"></div>
        <div><label class="form-label" for="employee_id">Karyawan</label><select class="form-select" id="employee_id" name="employee_id"><option value="">Semua Karyawan</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['id']) ?>"<?= (int) ($filters['employee_id'] ?? 0) === (int) $employee['id'] ? ' selected' : '' ?>><?= e($employee['name']) ?> (<?= e($employee['employee_code']) ?>)<?= (int) $employee['is_active'] === 1 ? '' : ' — Nonaktif' ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">Semua Status</option><option value="present"<?= $filters['status'] === 'present' ? ' selected' : '' ?>>Hadir</option><option value="late"<?= $filters['status'] === 'late' ? ' selected' : '' ?>>Terlambat</option></select></div>
        <div class="attendance-filter-actions"><a class="btn btn-outline-secondary" href="<?= e(url('/admin/attendances')) ?>">Reset Filter</a><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Terapkan</button></div>
    </form>
</section>

<section class="dashboard-panel" aria-labelledby="attendance-list-title">
    <div class="panel-heading"><h2 class="panel-title mb-0" id="attendance-list-title">Daftar Absensi</h2><span class="panel-count"><?= e($pagination['total_rows']) ?> data</span></div>
    <?php if ($attendances === []): ?>
        <div class="empty-state"><span class="empty-state-icon" aria-hidden="true"><i class="bi bi-calendar-x"></i></span><h3>Data absensi tidak ditemukan.</h3><p>Ubah atau reset filter untuk melihat data lainnya.</p></div>
    <?php else: ?>
        <div class="table-responsive d-none d-lg-block"><table class="table app-table align-middle mb-0"><thead><tr><th>Tanggal</th><th>Kode</th><th>Nama</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Status</th><th>Keterlambatan</th><th class="text-end">Aksi</th></tr></thead><tbody><?php foreach ($attendances as $attendance): ?><tr><td class="fw-semibold"><?= e(indonesian_short_date($attendance['attendance_date'])) ?></td><td><?= e($attendance['employee_code']) ?></td><td><?= e($attendance['name']) ?></td><td><?= e(format_attendance_time($attendance['check_in'])) ?></td><td><?= e(format_attendance_time($attendance['check_out'])) ?></td><td><span class="badge <?= e(attendance_status_class($attendance['status'])) ?>"><?= e(attendance_status_label($attendance['status'])) ?></span></td><td><?= (int) $attendance['late_minutes'] > 0 ? e((int) $attendance['late_minutes']) . ' menit' : '—' ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/attendances/show?id=' . $attendance['id'])) ?>">Detail</a></td></tr><?php endforeach; ?></tbody></table></div>
        <div class="mobile-record-list d-lg-none"><?php foreach ($attendances as $attendance): ?><article class="mobile-record"><div class="d-flex align-items-start justify-content-between gap-3"><div><h3><?= e($attendance['name']) ?></h3><p><?= e($attendance['employee_code']) ?> &middot; <?= e(indonesian_short_date($attendance['attendance_date'])) ?></p></div><span class="badge <?= e(attendance_status_class($attendance['status'])) ?>"><?= e(attendance_status_label($attendance['status'])) ?></span></div><div class="mobile-record-meta"><span><small>Masuk</small><strong><?= e(format_attendance_time($attendance['check_in'])) ?></strong></span><span><small>Pulang</small><strong><?= e(format_attendance_time($attendance['check_out'])) ?></strong></span></div><div class="d-flex align-items-center justify-content-between gap-3 mt-3"><small class="text-secondary"><?= (int) $attendance['late_minutes'] > 0 ? 'Terlambat ' . e((int) $attendance['late_minutes']) . ' menit' : 'Tepat waktu' ?></small><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/attendances/show?id=' . $attendance['id'])) ?>">Detail</a></div></article><?php endforeach; ?></div>
    <?php endif; ?>

    <?php if ($pagination['total_pages'] > 1): ?>
        <nav class="attendance-pagination" aria-label="Pagination data absensi"><span>Halaman <?= e($pagination['page']) ?> dari <?= e($pagination['total_pages']) ?></span><div class="btn-group"><a class="btn btn-outline-primary<?= $pagination['page'] <= 1 ? ' disabled' : '' ?>" href="<?= e(url($queryForPage(max(1, $pagination['page'] - 1)))) ?>"<?= $pagination['page'] <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>>Sebelumnya</a><a class="btn btn-outline-primary<?= $pagination['page'] >= $pagination['total_pages'] ? ' disabled' : '' ?>" href="<?= e(url($queryForPage(min($pagination['total_pages'], $pagination['page'] + 1)))) ?>"<?= $pagination['page'] >= $pagination['total_pages'] ? ' aria-disabled="true" tabindex="-1"' : '' ?>>Berikutnya</a></div></nav>
    <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Data Absensi - Attendance App';
$activeNavigation = 'admin-attendances';
require BASE_PATH . '/app/Views/layouts/app.php';
