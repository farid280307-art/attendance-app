<?php

declare(strict_types=1);

$success = is_string($success ?? null) ? $success : null;
$error = is_string($error ?? null) ? $error : null;

ob_start();
?>
<section class="management-heading mb-4" aria-labelledby="leave-title">
    <div>
        <p class="dashboard-eyebrow mb-1">Karyawan</p>
        <h1 class="dashboard-title" id="leave-title">Pengajuan</h1>
        <p class="dashboard-date mb-0">Kelola pengajuan Cuti, Sakit, dan Izin Anda.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/leave/create')) ?>">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Buat Pengajuan
    </a>
</section>

<?php if ($success !== null): ?>
    <div class="alert alert-success app-alert" role="alert"><i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error !== null): ?>
    <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><?= e($error) ?></div>
<?php endif; ?>

<section class="dashboard-panel" aria-labelledby="leave-list-title">
    <div class="panel-heading">
        <h2 class="panel-title mb-0" id="leave-list-title">Daftar Pengajuan Saya</h2>
        <span class="panel-count"><?= e(count($requests)) ?> data</span>
    </div>

    <?php if ($requests === []): ?>
        <div class="empty-state">
            <span class="empty-state-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
            <h3>Belum ada pengajuan.</h3>
            <p>Pengajuan yang sudah dikirim akan tampil di sini.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive d-none d-md-block">
            <table class="table app-table align-middle mb-0">
                <thead><tr><th>Jenis</th><th>Tanggal</th><th>Alasan</th><th>Status</th><th>Tanggal Pengajuan</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td class="fw-semibold"><?= e(leave_type_label($request['type'])) ?></td>
                        <td><?= e(indonesian_date_range($request['start_date'], $request['end_date'])) ?></td>
                        <td class="leave-reason-cell"><?= e(text_excerpt($request['reason'])) ?></td>
                        <td><span class="badge <?= e(leave_status_class($request['status'])) ?>"><?= e(leave_status_label($request['status'])) ?></span></td>
                        <td><?= e(format_app_datetime($request['created_at'])) ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/leave/show?id=' . $request['id'])) ?>">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mobile-record-list d-md-none">
            <?php foreach ($requests as $request): ?>
                <article class="mobile-record">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div><h3><?= e(leave_type_label($request['type'])) ?></h3><p><?= e(indonesian_date_range($request['start_date'], $request['end_date'])) ?></p></div>
                        <span class="badge <?= e(leave_status_class($request['status'])) ?>"><?= e(leave_status_label($request['status'])) ?></span>
                    </div>
                    <p class="leave-mobile-reason"><?= e(text_excerpt($request['reason'], 120)) ?></p>
                    <div class="d-flex align-items-center justify-content-between gap-3 mt-3">
                        <small class="text-secondary"><?= e(format_app_datetime($request['created_at'])) ?></small>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/leave/show?id=' . $request['id'])) ?>">Detail</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Pengajuan Saya - Attendance App';
$activeNavigation = 'leave';
require BASE_PATH . '/app/Views/layouts/app.php';
