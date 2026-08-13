<?php

declare(strict_types=1);

$success = is_string($success ?? null) ? $success : null;
$error = is_string($error ?? null) ? $error : null;
ob_start();
?>
<section class="management-heading mb-4"><div><p class="dashboard-eyebrow mb-1">Kalender</p><h1 class="dashboard-title">Hari Libur</h1><p class="dashboard-date mb-0">Kelola hari libur nasional dan perusahaan tanpa menghapus histori.</p></div><a class="btn btn-primary" href="<?= e(url('/admin/holidays/create')) ?>"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Hari Libur</a></section>
<?php if ($success !== null): ?><div class="alert alert-success app-alert" role="alert"><i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= e($success) ?></div><?php endif; ?>
<?php if ($error !== null): ?><div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><?= e($error) ?></div><?php endif; ?>
<div class="alert alert-info" role="status"><i class="bi bi-info-circle me-2" aria-hidden="true"></i>Perubahan hari libur tanggal lampau tidak menimpa snapshot kalender historis yang sudah tersedia.</div>
<section class="dashboard-panel" aria-labelledby="holiday-list-title">
    <div class="panel-heading"><h2 class="panel-title mb-0" id="holiday-list-title">Daftar Hari Libur</h2><span class="panel-count"><?= e(count($holidays)) ?> data</span></div>
    <?php if ($holidays === []): ?>
        <div class="empty-state"><span class="empty-state-icon" aria-hidden="true"><i class="bi bi-calendar-event"></i></span><h3>Belum ada hari libur.</h3><p>Tambahkan hari libur agar kalender kerja dapat mengenali tanggal tersebut.</p><a class="btn btn-primary mt-3" href="<?= e(url('/admin/holidays/create')) ?>">Tambah Hari Libur</a></div>
    <?php else: ?>
        <div class="table-responsive d-none d-md-block"><table class="table app-table align-middle mb-0"><thead><tr><th>Tanggal</th><th>Nama</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody><?php foreach ($holidays as $holiday): ?><tr><td class="fw-semibold"><?= e(indonesian_short_date($holiday['holiday_date'])) ?></td><td><?= e($holiday['name']) ?></td><td><span class="badge <?= (int) $holiday['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $holiday['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td><td><div class="record-actions justify-content-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/holidays/edit?id=' . $holiday['id'])) ?>">Edit</a><form method="post" action="<?= e(url('/admin/holidays/toggle')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($holiday['id']) ?>"><button class="btn btn-sm <?= (int) $holiday['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= (int) $holiday['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></div></td></tr><?php endforeach; ?></tbody></table></div>
        <div class="mobile-record-list d-md-none"><?php foreach ($holidays as $holiday): ?><article class="mobile-record"><div class="d-flex align-items-start justify-content-between gap-3"><div><h3><?= e($holiday['name']) ?></h3><p><?= e(indonesian_short_date($holiday['holiday_date'])) ?></p></div><span class="badge <?= (int) $holiday['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $holiday['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></div><div class="record-actions mt-3"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/holidays/edit?id=' . $holiday['id'])) ?>">Edit</a><form method="post" action="<?= e(url('/admin/holidays/toggle')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($holiday['id']) ?>"><button class="btn btn-sm <?= (int) $holiday['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= (int) $holiday['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></div></article><?php endforeach; ?></div>
    <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Hari Libur - Attendance App';
$activeNavigation = 'holidays';
require BASE_PATH . '/app/Views/layouts/app.php';
