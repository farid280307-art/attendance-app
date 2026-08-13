<?php

declare(strict_types=1);

$formAction = '/admin/employees/update';
$cancelUrl = '/admin/employees/show?id=' . $employeeId;
$submitLabel = 'Simpan Perubahan';
$isCreate = false;
$error = is_string($error ?? null) ? $error : null;

ob_start();
?>
<section class="management-heading mb-4"><div><p class="dashboard-eyebrow mb-1">Manajemen Karyawan</p><h1 class="dashboard-title">Edit Karyawan</h1><p class="dashboard-date mb-0">Perbarui data akun tanpa mengubah role atau password.</p></div></section>
<section class="dashboard-panel form-panel mx-auto"><?php require __DIR__ . '/_form.php'; ?></section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Edit Karyawan - Attendance App';
$activeNavigation = 'employees';
require BASE_PATH . '/app/Views/layouts/app.php';
