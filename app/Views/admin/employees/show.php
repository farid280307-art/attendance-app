<?php

declare(strict_types=1);

$success = is_string($success ?? null) ? $success : null;
$error = is_string($error ?? null) ? $error : null;
$isActive = (int) $employee['is_active'] === 1;
$hasSchedule = $employee['work_schedule_id'] !== null;
$timeLabel = static function (mixed $value): string {
    if (!is_string($value) || $value === '') {
        return '-';
    }

    return substr($value, 0, 5);
};
$summaryItems = [
    ['key' => 'present', 'label' => 'Hadir', 'icon' => 'bi-check-circle', 'tone' => 'is-success'],
    ['key' => 'late', 'label' => 'Terlambat', 'icon' => 'bi-clock-history', 'tone' => 'is-warning'],
    ['key' => 'leave', 'label' => 'Cuti', 'icon' => 'bi-calendar-check', 'tone' => 'is-primary'],
    ['key' => 'sick', 'label' => 'Sakit', 'icon' => 'bi-heart-pulse', 'tone' => 'is-danger'],
    ['key' => 'permission', 'label' => 'Izin', 'icon' => 'bi-file-earmark-text', 'tone' => 'is-info'],
];

ob_start();
?>
<section class="management-heading mb-4" aria-labelledby="employee-detail-heading">
    <div>
        <a class="small text-decoration-none" href="<?= e(url('/admin/employees')) ?>"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Kembali ke daftar</a>
        <h1 class="dashboard-title mt-2" id="employee-detail-heading">Detail Karyawan</h1>
        <p class="dashboard-date mb-0">Informasi akun, jadwal kerja, dan ringkasan kehadiran.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= e(url('/admin/employees/edit?id=' . $employee['id'])) ?>"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit Data</a>
</section>

<?php if ($success !== null): ?><div class="alert alert-success app-alert" role="alert"><i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= e($success) ?></div><?php endif; ?>
<?php if ($error !== null): ?><div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><?= e($error) ?></div><?php endif; ?>

<div class="employee-detail-layout">
    <section class="dashboard-panel" aria-labelledby="employee-account-title">
        <div class="panel-heading"><h2 class="panel-title mb-0" id="employee-account-title">Informasi Akun</h2><span class="badge <?= $isActive ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $isActive ? 'Aktif' : 'Nonaktif' ?></span></div>
        <dl class="employee-detail-grid">
            <div><dt>Nama Lengkap</dt><dd><?= e($employee['name']) ?></dd></div>
            <div><dt>Kode Karyawan</dt><dd><?= e($employee['employee_code']) ?></dd></div>
            <div><dt>Username</dt><dd><?= e($employee['username']) ?></dd></div>
            <div><dt>Role</dt><dd>Karyawan</dd></div>
            <div><dt>Tanggal Akun Dibuat</dt><dd><?= e(format_app_datetime((string) $employee['created_at'])) ?></dd></div>
        </dl>
    </section>

    <section class="dashboard-panel" aria-labelledby="employee-schedule-title">
        <div class="panel-heading"><h2 class="panel-title mb-0" id="employee-schedule-title">Jadwal Kerja</h2><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/work-schedules')) ?>">Atur Jadwal</a></div>
        <?php if ($hasSchedule): ?>
            <dl class="employee-detail-grid">
                <div><dt>Nama Jadwal</dt><dd><?= e($employee['work_schedule_name']) ?><?php if ((int) ($employee['work_schedule_active'] ?? 0) !== 1): ?> <span class="badge text-bg-secondary">Nonaktif</span><?php endif; ?></dd></div>
                <div><dt>Jam Kerja</dt><dd><?= e($timeLabel($employee['work_schedule_start_time'])) ?>–<?= e($timeLabel($employee['work_schedule_end_time'])) ?> WIB</dd></div>
                <div><dt>Toleransi Terlambat</dt><dd><?= e((int) $employee['work_schedule_late_tolerance_minutes']) ?> menit</dd></div>
            </dl>
        <?php else: ?>
            <div class="empty-state employee-schedule-empty"><span class="empty-state-icon" aria-hidden="true"><i class="bi bi-calendar-x"></i></span><h3>Jadwal kerja belum ditentukan.</h3><p>Tentukan jadwal melalui menu Jadwal Kerja agar karyawan dapat melakukan absensi.</p></div>
        <?php endif; ?>
    </section>
</div>

<section class="dashboard-panel mt-4" aria-labelledby="employee-summary-title">
    <div class="panel-heading"><h2 class="panel-title mb-0" id="employee-summary-title">Ringkasan <?= e($monthLabel) ?></h2><span class="panel-count">Bulan berjalan</span></div>
    <div class="monthly-summary-grid employee-summary-grid">
        <?php foreach ($summaryItems as $item): ?>
            <div class="monthly-summary-item"><span class="monthly-summary-icon <?= e($item['tone']) ?>" aria-hidden="true"><i class="bi <?= e($item['icon']) ?>"></i></span><span><strong><?= e((int) ($monthlySummary[$item['key']] ?? 0)) ?></strong><small><?= e($item['label']) ?></small></span></div>
        <?php endforeach; ?>
    </div>
</section>

<section class="dashboard-panel mt-4" aria-labelledby="employee-security-title">
    <div class="panel-heading"><h2 class="panel-title mb-0" id="employee-security-title">Akun dan Keamanan</h2></div>
    <div class="employee-security-grid">
        <div class="employee-action-panel">
            <h3><?= $isActive ? 'Nonaktifkan Akun' : 'Aktifkan Akun' ?></h3>
            <p><?= $isActive ? 'Karyawan yang dinonaktifkan tidak dapat login sampai akunnya diaktifkan kembali.' : 'Karyawan dapat kembali login setelah akunnya diaktifkan.' ?></p>
            <form method="post" action="<?= e(url('/admin/employees/toggle')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= e($employee['id']) ?>">
                <button class="btn <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= $isActive ? 'Nonaktifkan Akun' : 'Aktifkan Akun' ?></button>
            </form>
        </div>

        <div class="employee-action-panel">
            <h3>Reset Password</h3>
            <p>Tetapkan password baru tanpa menampilkan atau mengubah data password lama.</p>
            <form method="post" action="<?= e(url('/admin/employees/reset-password')) ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= e($employee['id']) ?>">
                <div class="mb-3"><label class="form-label" for="password">Password Baru</label><input class="form-control<?= isset($passwordErrors['password']) ? ' is-invalid' : '' ?>" id="password" name="password" type="password" minlength="8" maxlength="255" autocomplete="new-password" required><?php if (isset($passwordErrors['password'])): ?><div class="invalid-feedback"><?= e($passwordErrors['password']) ?></div><?php endif; ?></div>
                <div class="mb-3"><label class="form-label" for="password_confirmation">Konfirmasi Password Baru</label><input class="form-control<?= isset($passwordErrors['password_confirmation']) ? ' is-invalid' : '' ?>" id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="255" autocomplete="new-password" required><?php if (isset($passwordErrors['password_confirmation'])): ?><div class="invalid-feedback"><?= e($passwordErrors['password_confirmation']) ?></div><?php endif; ?></div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-key me-1" aria-hidden="true"></i>Reset Password</button>
            </form>
        </div>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Detail Karyawan - Attendance App';
$activeNavigation = 'employees';
require BASE_PATH . '/app/Views/layouts/app.php';
