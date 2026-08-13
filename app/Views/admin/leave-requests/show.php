<?php

declare(strict_types=1);

$errors = is_array($errors ?? null) ? $errors : [];
$success = is_string($success ?? null) ? $success : null;
$error = is_string($error ?? null) ? $error : null;
$rejectionReason = is_string($rejectionReason ?? null) ? $rejectionReason : '';

ob_start();
?>
<section class="management-heading mb-4">
    <div>
        <p class="dashboard-eyebrow mb-1">Pengajuan Karyawan</p>
        <h1 class="dashboard-title">Detail Pengajuan #<?= e($request['id']) ?></h1>
        <p class="dashboard-date mb-0">Review informasi pengajuan sebelum memberi keputusan.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/leave-requests')) ?>"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Kembali</a>
</section>

<?php if ($success !== null): ?><div class="alert alert-success app-alert" role="alert"><i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= e($success) ?></div><?php endif; ?>
<?php if ($error !== null): ?><div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><?= e($error) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-12<?= $request['status'] === 'pending' ? ' col-xl-7' : '' ?>">
        <section class="dashboard-panel leave-detail-panel h-100">
            <div class="panel-heading">
                <div><p class="panel-kicker mb-1"><?= e(leave_type_label($request['type'])) ?></p><h2 class="panel-title mb-0"><?= e($request['name']) ?> · <?= e($request['employee_code']) ?></h2></div>
                <span class="badge <?= e(leave_status_class($request['status'])) ?>"><?= e(leave_status_label($request['status'])) ?></span>
            </div>
            <div class="leave-detail-body">
                <dl class="leave-detail-grid">
                    <div><dt>Nama Karyawan</dt><dd><?= e($request['name']) ?></dd></div>
                    <div><dt>Kode Karyawan</dt><dd><?= e($request['employee_code']) ?></dd></div>
                    <div><dt>Tanggal Mulai</dt><dd><?= e(indonesian_short_date($request['start_date'])) ?></dd></div>
                    <div><dt>Tanggal Selesai</dt><dd><?= e(indonesian_short_date($request['end_date'])) ?></dd></div>
                    <div><dt>Tanggal Diajukan</dt><dd><?= e(format_app_datetime($request['created_at'])) ?></dd></div>
                    <div><dt>Status</dt><dd><?= e(leave_status_label($request['status'])) ?></dd></div>
                </dl>

                <div class="leave-detail-section"><h3>Alasan</h3><p><?= nl2br(e($request['reason'])) ?></p></div>

                <div class="leave-detail-section"><h3>Lampiran</h3><?php if ($request['attachment'] !== null): ?><a class="btn btn-outline-primary" target="_blank" rel="noopener" href="<?= e(url('/leave/attachment?id=' . $request['id'])) ?>"><i class="bi bi-paperclip me-1" aria-hidden="true"></i>Lihat Lampiran</a><?php else: ?><p class="text-secondary mb-0">Tidak ada lampiran.</p><?php endif; ?></div>

                <?php if ($request['status'] !== 'pending'): ?>
                    <div class="leave-detail-section mb-0">
                        <h3>Informasi Review</h3>
                        <p class="mb-1">Reviewer: <strong><?= e($request['reviewer_name'] ?? 'Administrator') ?></strong></p>
                        <p class="mb-0">Tanggal review: <strong><?= e(format_app_datetime($request['reviewed_at'])) ?></strong></p>
                        <?php if ($request['status'] === 'rejected'): ?><div class="alert alert-danger mt-3 mb-0"><strong>Alasan penolakan</strong><p class="mb-0 mt-1"><?= nl2br(e($request['rejection_reason'])) ?></p></div><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php if ($request['status'] === 'pending'): ?>
        <div class="col-12 col-xl-5">
            <section class="dashboard-panel leave-review-panel" aria-labelledby="review-title">
                <div class="panel-heading"><div><p class="panel-kicker mb-1">Keputusan Admin</p><h2 class="panel-title mb-0" id="review-title">Proses Pengajuan</h2></div></div>
                <div class="leave-detail-body">
                    <form method="post" action="<?= e(url('/admin/leave-requests/approve')) ?>" class="mb-4">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($request['id']) ?>">
                        <button class="btn btn-success w-100" type="submit"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Setujui</button>
                    </form>

                    <div class="leave-review-divider"><span>atau tolak</span></div>

                    <form method="post" action="<?= e(url('/admin/leave-requests/reject')) ?>" novalidate>
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($request['id']) ?>">
                        <div class="mb-3">
                            <label class="form-label" for="rejection_reason">Alasan Penolakan</label>
                            <textarea class="form-control<?= isset($errors['rejection_reason']) ? ' is-invalid' : '' ?>" id="rejection_reason" name="rejection_reason" rows="4" minlength="5" maxlength="1000" required><?= e($rejectionReason) ?></textarea>
                            <div class="form-text">Wajib diisi, minimal 5 karakter.</div>
                            <?php if (isset($errors['rejection_reason'])): ?><div class="invalid-feedback"><?= e($errors['rejection_reason']) ?></div><?php endif; ?>
                        </div>
                        <button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-x-circle me-1" aria-hidden="true"></i>Tolak Pengajuan</button>
                    </form>
                </div>
            </section>
        </div>
    <?php endif; ?>
</div>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Detail Pengajuan Karyawan - Attendance App';
$activeNavigation = 'admin-leave';
require BASE_PATH . '/app/Views/layouts/app.php';
