<?php

declare(strict_types=1);

ob_start();
?>
<section class="management-heading mb-4">
    <div>
        <p class="dashboard-eyebrow mb-1">Pengajuan Saya</p>
        <h1 class="dashboard-title">Detail Pengajuan #<?= e($request['id']) ?></h1>
        <p class="dashboard-date mb-0">Informasi dan status pengajuan Anda.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/leave')) ?>"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Kembali</a>
</section>

<section class="dashboard-panel leave-detail-panel mx-auto">
    <div class="panel-heading">
        <div><p class="panel-kicker mb-1"><?= e(leave_type_label($request['type'])) ?></p><h2 class="panel-title mb-0"><?= e(indonesian_date_range($request['start_date'], $request['end_date'])) ?></h2></div>
        <span class="badge <?= e(leave_status_class($request['status'])) ?>"><?= e(leave_status_label($request['status'])) ?></span>
    </div>
    <div class="leave-detail-body">
        <dl class="leave-detail-grid">
            <div><dt>Tanggal Mulai</dt><dd><?= e(indonesian_short_date($request['start_date'])) ?></dd></div>
            <div><dt>Tanggal Selesai</dt><dd><?= e(indonesian_short_date($request['end_date'])) ?></dd></div>
            <div><dt>Tanggal Pengajuan</dt><dd><?= e(format_app_datetime($request['created_at'])) ?></dd></div>
            <div><dt>Status</dt><dd><?= e(leave_status_label($request['status'])) ?></dd></div>
        </dl>

        <div class="leave-detail-section"><h3>Alasan</h3><p><?= nl2br(e($request['reason'])) ?></p></div>

        <?php if ($request['attachment'] !== null): ?>
            <div class="leave-detail-section"><h3>Lampiran</h3><a class="btn btn-outline-primary" target="_blank" rel="noopener" href="<?= e(url('/leave/attachment?id=' . $request['id'])) ?>"><i class="bi bi-paperclip me-1" aria-hidden="true"></i>Lihat Lampiran</a></div>
        <?php endif; ?>

        <?php if ($request['status'] === 'approved'): ?>
            <div class="alert alert-success mb-0" role="status"><strong>Disetujui oleh admin.</strong><br><span>Direview pada <?= e(format_app_datetime($request['reviewed_at'])) ?>.</span></div>
        <?php elseif ($request['status'] === 'rejected'): ?>
            <div class="alert alert-danger mb-0" role="status"><strong>Pengajuan ditolak.</strong><p class="mb-1 mt-2"><?= nl2br(e($request['rejection_reason'])) ?></p><small>Direview pada <?= e(format_app_datetime($request['reviewed_at'])) ?>.</small></div>
        <?php endif; ?>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Detail Pengajuan - Attendance App';
$activeNavigation = 'leave';
require BASE_PATH . '/app/Views/layouts/app.php';
