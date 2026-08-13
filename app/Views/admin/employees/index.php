<?php

declare(strict_types=1);

$success = is_string($success ?? null) ? $success : null;
$error = is_string($error ?? null) ? $error : null;

ob_start();
?>
<section class="management-heading mb-4" aria-labelledby="employees-heading">
    <div>
        <p class="dashboard-eyebrow mb-1">Administrasi</p>
        <h1 class="dashboard-title" id="employees-heading">Karyawan</h1>
        <p class="dashboard-date mb-0">Kelola akun dan data dasar karyawan.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/admin/employees/create')) ?>"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>Tambah Karyawan</a>
</section>

<?php if ($success !== null): ?><div class="alert alert-success app-alert" role="alert"><i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= e($success) ?></div><?php endif; ?>
<?php if ($error !== null): ?><div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><?= e($error) ?></div><?php endif; ?>

<section class="dashboard-panel" aria-labelledby="employee-list-title">
    <div class="panel-heading"><h2 class="panel-title mb-0" id="employee-list-title">Daftar Karyawan</h2><span class="panel-count"><?= e(count($employees)) ?> data</span></div>

    <?php if ($employees === []): ?>
        <div class="empty-state"><span class="empty-state-icon" aria-hidden="true"><i class="bi bi-people"></i></span><h3>Belum ada data karyawan.</h3><p>Tambahkan karyawan pertama untuk membuat akun.</p><a class="btn btn-primary mt-3" href="<?= e(url('/admin/employees/create')) ?>">Tambah Karyawan</a></div>
    <?php else: ?>
        <div class="table-responsive d-none d-lg-block">
            <table class="table app-table align-middle mb-0">
                <thead><tr><th>Kode Karyawan</th><th>Nama</th><th>Username</th><th>Jadwal Kerja</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($employee['employee_code']) ?></td>
                        <td><?= e($employee['name']) ?></td>
                        <td><?= e($employee['username']) ?></td>
                        <td><?= e($employee['work_schedule_name'] ?? 'Belum ditentukan') ?></td>
                        <td><span class="badge <?= (int) $employee['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $employee['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td><div class="record-actions justify-content-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/employees/show?id=' . $employee['id'])) ?>">Detail</a><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/employees/edit?id=' . $employee['id'])) ?>">Edit</a><form method="post" action="<?= e(url('/admin/employees/toggle')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($employee['id']) ?>"><button class="btn btn-sm <?= (int) $employee['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= (int) $employee['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mobile-record-list d-lg-none">
            <?php foreach ($employees as $employee): ?>
                <article class="mobile-record">
                    <div class="d-flex align-items-start justify-content-between gap-3"><div><h3><?= e($employee['name']) ?></h3><p><?= e($employee['employee_code']) ?> · <?= e($employee['username']) ?></p></div><span class="badge <?= (int) $employee['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $employee['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></div>
                    <p class="employee-schedule-copy"><small>Jadwal Kerja</small><strong><?= e($employee['work_schedule_name'] ?? 'Belum ditentukan') ?></strong></p>
                    <div class="record-actions"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/employees/show?id=' . $employee['id'])) ?>">Detail</a><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/employees/edit?id=' . $employee['id'])) ?>">Edit</a><form method="post" action="<?= e(url('/admin/employees/toggle')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($employee['id']) ?>"><button class="btn btn-sm <?= (int) $employee['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= (int) $employee['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Karyawan - Attendance App';
$activeNavigation = 'employees';
require BASE_PATH . '/app/Views/layouts/app.php';
