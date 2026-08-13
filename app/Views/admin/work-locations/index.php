<?php

declare(strict_types=1);

$success = is_string($success ?? null) ? $success : null;
ob_start();
?>
<section class="management-heading mb-4">
    <div>
        <p class="dashboard-eyebrow mb-1">Master Data</p>
        <h1 class="dashboard-title">Lokasi Kerja</h1>
        <p class="dashboard-date mb-0">Kelola titik pusat dan radius lokasi yang diizinkan untuk absensi.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/admin/work-locations/create')) ?>"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Lokasi</a>
</section>

<?php if ($success !== null): ?>
    <div class="alert alert-success app-alert" role="alert"><i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= e($success) ?></div>
<?php endif; ?>

<section class="dashboard-panel" aria-labelledby="locations-title">
    <div class="panel-heading">
        <h2 class="panel-title mb-0" id="locations-title">Daftar Lokasi</h2>
        <span class="panel-count"><?= e(count($locations)) ?> data</span>
    </div>
    <?php if ($locations === []): ?>
        <div class="empty-state"><span class="empty-state-icon"><i class="bi bi-geo-alt"></i></span><h3>Belum ada lokasi kerja.</h3><p>Tambahkan lokasi pertama untuk mulai menyiapkan radius absensi.</p></div>
    <?php else: ?>
        <div class="table-responsive d-none d-md-block">
            <table class="table app-table align-middle mb-0">
                <thead><tr><th>Nama Lokasi</th><th>Latitude</th><th>Longitude</th><th>Radius</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($locations as $location): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($location['name']) ?></td>
                        <td><?= e($location['latitude']) ?></td>
                        <td><?= e($location['longitude']) ?></td>
                        <td><?= e($location['radius_meters']) ?> m</td>
                        <td><span class="badge <?= (int) $location['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $location['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td><div class="record-actions justify-content-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/work-locations/edit?id=' . $location['id'])) ?>">Edit</a><form method="post" action="<?= e(url('/admin/work-locations/toggle')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($location['id']) ?>"><button class="btn btn-sm <?= (int) $location['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= (int) $location['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mobile-record-list d-md-none">
            <?php foreach ($locations as $location): ?>
                <article class="mobile-record">
                    <div class="d-flex justify-content-between gap-3"><div><h3><?= e($location['name']) ?></h3><p><?= e($location['latitude']) ?>, <?= e($location['longitude']) ?></p></div><span class="badge align-self-start <?= (int) $location['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $location['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></div>
                    <p class="mb-3"><small>Radius</small><br><strong><?= e($location['radius_meters']) ?> meter</strong></p>
                    <div class="record-actions"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/work-locations/edit?id=' . $location['id'])) ?>">Edit</a><form method="post" action="<?= e(url('/admin/work-locations/toggle')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($location['id']) ?>"><button class="btn btn-sm <?= (int) $location['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= (int) $location['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Lokasi Kerja - Attendance App';
$activeNavigation = 'work-locations';
require BASE_PATH . '/app/Views/layouts/app.php';
