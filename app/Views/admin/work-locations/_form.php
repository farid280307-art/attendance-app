<?php

declare(strict_types=1);

$fieldClass = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
?>
<form method="post" action="<?= e(url($formAction)) ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($locationId !== null): ?>
        <input type="hidden" name="id" value="<?= e($locationId) ?>">
    <?php endif; ?>

    <div class="mb-3">
        <label class="form-label" for="name">Nama Lokasi</label>
        <input class="form-control<?= $fieldClass('name') ?>" id="name" name="name" type="text" maxlength="100" value="<?= e($formData['name'] ?? '') ?>" required autofocus>
        <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label class="form-label" for="latitude">Latitude</label>
            <input class="form-control<?= $fieldClass('latitude') ?>" id="latitude" name="latitude" type="number" step="0.0000001" min="-90" max="90" value="<?= e($formData['latitude'] ?? '') ?>" required>
            <?php if (isset($errors['latitude'])): ?><div class="invalid-feedback"><?= e($errors['latitude']) ?></div><?php endif; ?>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="longitude">Longitude</label>
            <input class="form-control<?= $fieldClass('longitude') ?>" id="longitude" name="longitude" type="number" step="0.0000001" min="-180" max="180" value="<?= e($formData['longitude'] ?? '') ?>" required>
            <?php if (isset($errors['longitude'])): ?><div class="invalid-feedback"><?= e($errors['longitude']) ?></div><?php endif; ?>
        </div>
    </div>
    <p class="form-text mb-3">Koordinat ini akan menjadi pusat radius absensi.</p>

    <div class="mb-3">
        <label class="form-label" for="radius_meters">Radius Absensi (meter)</label>
        <input class="form-control<?= $fieldClass('radius_meters') ?>" id="radius_meters" name="radius_meters" type="number" min="1" max="10000" step="1" value="<?= e($formData['radius_meters'] ?? '') ?>" required>
        <?php if (isset($errors['radius_meters'])): ?><div class="invalid-feedback"><?= e($errors['radius_meters']) ?></div><?php endif; ?>
    </div>

    <div class="form-check form-switch mb-4">
        <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1"<?= (int) ($formData['is_active'] ?? 0) === 1 ? ' checked' : '' ?>>
        <label class="form-check-label" for="is_active">Lokasi aktif</label>
    </div>

    <div class="d-flex flex-column-reverse flex-sm-row gap-2 justify-content-end">
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/work-locations')) ?>">Batal</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i><?= e($submitLabel) ?></button>
    </div>
</form>
