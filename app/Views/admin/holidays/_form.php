<?php

declare(strict_types=1);

$fieldClass = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
?>
<form method="post" action="<?= e(url($formAction)) ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($holidayId !== null): ?><input type="hidden" name="id" value="<?= e($holidayId) ?>"><?php endif; ?>
    <div class="mb-3"><label class="form-label" for="holiday_date">Tanggal</label><input class="form-control<?= $fieldClass('holiday_date') ?>" id="holiday_date" name="holiday_date" type="date" value="<?= e($formData['holiday_date'] ?? '') ?>" required autofocus><?php if (isset($errors['holiday_date'])): ?><div class="invalid-feedback"><?= e($errors['holiday_date']) ?></div><?php endif; ?></div>
    <div class="mb-3"><label class="form-label" for="name">Nama Hari Libur</label><input class="form-control<?= $fieldClass('name') ?>" id="name" name="name" type="text" minlength="3" maxlength="150" value="<?= e($formData['name'] ?? '') ?>" required><?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?></div>
    <div class="form-check form-switch mb-4"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1"<?= (int) ($formData['is_active'] ?? 0) === 1 ? ' checked' : '' ?>><label class="form-check-label" for="is_active">Hari libur aktif</label></div>
    <div class="d-flex flex-column-reverse flex-sm-row gap-2 justify-content-end"><a class="btn btn-outline-secondary" href="<?= e(url('/admin/holidays')) ?>">Batal</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i><?= e($submitLabel) ?></button></div>
</form>
