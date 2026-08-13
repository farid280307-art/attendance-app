<?php

declare(strict_types=1);

$employeeId = null;
$formAction = '/admin/employees/store';
$cancelUrl = '/admin/employees';
$submitLabel = 'Simpan Karyawan';
$isCreate = true;
$error = is_string($error ?? null) ? $error : null;

ob_start();
?>
<section class="management-heading mb-4"><div><p class="dashboard-eyebrow mb-1">Manajemen Karyawan</p><h1 class="dashboard-title">Tambah Karyawan</h1><p class="dashboard-date mb-0">Buat akun employee baru tanpa penugasan jadwal otomatis.</p></div></section>
<section class="dashboard-panel form-panel mx-auto"><?php require __DIR__ . '/_form.php'; ?></section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Tambah Karyawan - Attendance App';
$activeNavigation = 'employees';
require BASE_PATH . '/app/Views/layouts/app.php';
