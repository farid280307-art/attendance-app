<?php

declare(strict_types=1);

$fieldClass = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
?>
<?php if ($error !== null): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if (isset($errors['form'])): ?><div class="alert alert-warning" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>

<form method="post" action="<?= e(url($formAction)) ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($employeeId !== null): ?><input type="hidden" name="id" value="<?= e($employeeId) ?>"><?php endif; ?>

    <div class="mb-3">
        <label class="form-label" for="name">Nama Lengkap</label>
        <input class="form-control<?= $fieldClass('name') ?>" id="name" name="name" type="text" minlength="3" maxlength="100" value="<?= e($formData['name'] ?? '') ?>" autocomplete="name" required autofocus>
        <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <label class="form-label" for="employee_code">Kode Karyawan</label>
            <input class="form-control<?= $fieldClass('employee_code') ?>" id="employee_code" name="employee_code" type="text" minlength="2" maxlength="50" value="<?= e($formData['employee_code'] ?? '') ?>" required>
            <?php if (isset($errors['employee_code'])): ?><div class="invalid-feedback"><?= e($errors['employee_code']) ?></div><?php endif; ?>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="username">Username</label>
            <input class="form-control<?= $fieldClass('username') ?>" id="username" name="username" type="text" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" value="<?= e($formData['username'] ?? '') ?>" autocomplete="username" required>
            <div class="form-text">Gunakan huruf, angka, titik, garis bawah, atau tanda hubung tanpa spasi.</div>
            <?php if (isset($errors['username'])): ?><div class="invalid-feedback"><?= e($errors['username']) ?></div><?php endif; ?>
        </div>
    </div>

    <?php if ($isCreate): ?>
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="password">Password</label>
                <input class="form-control<?= $fieldClass('password') ?>" id="password" name="password" type="password" minlength="8" maxlength="255" autocomplete="new-password" required>
                <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
                <input class="form-control<?= $fieldClass('password_confirmation') ?>" id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="255" autocomplete="new-password" required>
                <?php if (isset($errors['password_confirmation'])): ?><div class="invalid-feedback"><?= e($errors['password_confirmation']) ?></div><?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-check form-switch mb-3">
        <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1"<?= (int) ($formData['is_active'] ?? 0) === 1 ? ' checked' : '' ?>>
        <label class="form-check-label" for="is_active">Status akun aktif</label>
    </div>

    <div class="employee-form-note mb-4"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Jadwal kerja dapat ditentukan melalui menu Jadwal Kerja setelah data karyawan disimpan.</span></div>

    <div class="d-flex flex-column-reverse flex-sm-row gap-2 justify-content-end"><a class="btn btn-outline-secondary" href="<?= e(url($cancelUrl)) ?>">Batal</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i><?= e($submitLabel) ?></button></div>
</form>
