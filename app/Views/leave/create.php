<?php

declare(strict_types=1);

$errors = is_array($errors ?? null) ? $errors : [];
$error = is_string($error ?? null) ? $error : null;
$fieldClass = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';

ob_start();
?>
<section class="management-heading mb-4">
    <div>
        <p class="dashboard-eyebrow mb-1">Pengajuan</p>
        <h1 class="dashboard-title">Buat Pengajuan</h1>
        <p class="dashboard-date mb-0">Kirim pengajuan Cuti, Sakit, atau Izin beserta lampiran bila diperlukan.</p>
    </div>
</section>

<?php if ($error !== null): ?>
    <div class="alert alert-danger mx-auto leave-form-alert" role="alert"><i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><?= e($error) ?></div>
<?php endif; ?>

<section class="dashboard-panel form-panel mx-auto">
    <form method="post" action="<?= e(url('/leave/store')) ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>

        <?php if (isset($errors['date_range'])): ?>
            <div class="alert alert-warning" role="alert"><?= e($errors['date_range']) ?></div>
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label" for="type">Jenis Pengajuan</label>
            <select class="form-select<?= $fieldClass('type') ?>" id="type" name="type" required autofocus>
                <option value="">Pilih jenis pengajuan</option>
                <?php foreach (['leave' => 'Cuti', 'sick' => 'Sakit', 'permission' => 'Izin'] as $value => $label): ?>
                    <option value="<?= e($value) ?>"<?= ($formData['type'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['type'])): ?><div class="invalid-feedback"><?= e($errors['type']) ?></div><?php endif; ?>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6">
                <label class="form-label" for="start_date">Tanggal Mulai</label>
                <input class="form-control<?= $fieldClass('start_date') ?>" id="start_date" name="start_date" type="date" value="<?= e($formData['start_date'] ?? '') ?>" required>
                <?php if (isset($errors['start_date'])): ?><div class="invalid-feedback"><?= e($errors['start_date']) ?></div><?php endif; ?>
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label" for="end_date">Tanggal Selesai</label>
                <input class="form-control<?= $fieldClass('end_date') ?>" id="end_date" name="end_date" type="date" value="<?= e($formData['end_date'] ?? '') ?>" required>
                <?php if (isset($errors['end_date'])): ?><div class="invalid-feedback"><?= e($errors['end_date']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="reason">Alasan</label>
            <textarea class="form-control<?= $fieldClass('reason') ?>" id="reason" name="reason" rows="5" minlength="5" maxlength="2000" required><?= e($formData['reason'] ?? '') ?></textarea>
            <div class="form-text">Tuliskan alasan antara 5 sampai 2.000 karakter.</div>
            <?php if (isset($errors['reason'])): ?><div class="invalid-feedback"><?= e($errors['reason']) ?></div><?php endif; ?>
        </div>

        <div class="mb-4">
            <label class="form-label" for="attachment">Lampiran <span class="text-secondary fw-normal">(opsional)</span></label>
            <input class="form-control<?= $fieldClass('attachment') ?>" id="attachment" name="attachment" type="file" accept="application/pdf,image/jpeg,image/png">
            <div class="form-text">PDF, JPEG, atau PNG. Maksimal 5 MB.</div>
            <?php if (isset($errors['attachment'])): ?><div class="invalid-feedback"><?= e($errors['attachment']) ?></div><?php endif; ?>
        </div>

        <div class="d-flex flex-column-reverse flex-sm-row gap-2 justify-content-end">
            <a class="btn btn-outline-secondary" href="<?= e(url('/leave')) ?>">Batal</a>
            <button class="btn btn-primary" type="submit"><i class="bi bi-send me-1" aria-hidden="true"></i>Kirim Pengajuan</button>
        </div>
    </form>
</section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Buat Pengajuan - Attendance App';
$activeNavigation = 'leave';
require BASE_PATH . '/app/Views/layouts/app.php';
