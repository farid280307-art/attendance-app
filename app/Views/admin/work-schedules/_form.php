<?php

declare(strict_types=1);

$fieldClass = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
$weekdayLabels = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$selectedWorkingDays = is_array($formData['working_days'] ?? null)
    ? array_map('intval', $formData['working_days'])
    : [];
?>
<form method="post" action="<?= e(url($formAction)) ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($scheduleId !== null): ?><input type="hidden" name="id" value="<?= e($scheduleId) ?>"><?php endif; ?>
    <div class="mb-3">
        <label class="form-label" for="name">Nama Jadwal</label>
        <input class="form-control<?= $fieldClass('name') ?>" id="name" name="name" type="text" maxlength="100" value="<?= e($formData['name'] ?? '') ?>" required autofocus>
        <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6"><label class="form-label" for="start_time">Jam Masuk</label><input class="form-control<?= $fieldClass('start_time') ?>" id="start_time" name="start_time" type="time" value="<?= e($formData['start_time'] ?? '') ?>" required><?php if (isset($errors['start_time'])): ?><div class="invalid-feedback"><?= e($errors['start_time']) ?></div><?php endif; ?></div>
        <div class="col-12 col-md-6"><label class="form-label" for="end_time">Jam Pulang</label><input class="form-control<?= $fieldClass('end_time') ?>" id="end_time" name="end_time" type="time" value="<?= e($formData['end_time'] ?? '') ?>" required><?php if (isset($errors['end_time'])): ?><div class="invalid-feedback"><?= e($errors['end_time']) ?></div><?php endif; ?></div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="late_tolerance_minutes">Toleransi Terlambat (menit)</label>
        <input class="form-control<?= $fieldClass('late_tolerance_minutes') ?>" id="late_tolerance_minutes" name="late_tolerance_minutes" type="number" min="0" max="180" step="1" value="<?= e($formData['late_tolerance_minutes'] ?? '') ?>" required>
        <?php if (isset($errors['late_tolerance_minutes'])): ?><div class="invalid-feedback"><?= e($errors['late_tolerance_minutes']) ?></div><?php endif; ?>
    </div>
    <fieldset class="mb-3">
        <legend class="form-label mb-2">Hari Kerja</legend>
        <div class="working-day-options<?= isset($errors['working_days']) ? ' is-invalid' : '' ?>">
            <?php foreach ($weekdayLabels as $weekday => $label): ?>
                <div class="form-check">
                    <input class="form-check-input" id="working_day_<?= e($weekday) ?>" name="working_days[]" type="checkbox" value="<?= e($weekday) ?>"<?= in_array($weekday, $selectedWorkingDays, true) ? ' checked' : '' ?>>
                    <label class="form-check-label" for="working_day_<?= e($weekday) ?>"><?= e($label) ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (isset($errors['working_days'])): ?><div class="invalid-feedback d-block"><?= e($errors['working_days']) ?></div><?php endif; ?>
        <div class="form-text">Pilih minimal satu hari. Hari yang tidak dipilih dianggap libur mingguan.</div>
    </fieldset>
    <div class="form-check form-switch mb-4"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1"<?= (int) ($formData['is_active'] ?? 0) === 1 ? ' checked' : '' ?>><label class="form-check-label" for="is_active">Jadwal aktif</label></div>
    <div class="d-flex flex-column-reverse flex-sm-row gap-2 justify-content-end"><a class="btn btn-outline-secondary" href="<?= e(url('/admin/work-schedules')) ?>">Batal</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i><?= e($submitLabel) ?></button></div>
</form>
