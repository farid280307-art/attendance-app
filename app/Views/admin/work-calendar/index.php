<?php

declare(strict_types=1);

$success = is_string($success ?? null) ? $success : null;
$error = is_string($error ?? null) ? $error : null;
$monthError = is_string($monthError ?? null) ? $monthError : null;
ob_start();
?>
<section class="management-heading mb-4"><div><p class="dashboard-eyebrow mb-1">Kalender</p><h1 class="dashboard-title">Kalender Kerja</h1><p class="dashboard-date mb-0">Generate snapshot jadwal karyawan sebagai dasar penentuan Alpha.</p></div></section>
<?php if ($success !== null): ?><div class="alert alert-success app-alert" role="alert"><i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= e($success) ?></div><?php endif; ?>
<?php if ($error !== null): ?><div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><?= e($error) ?></div><?php endif; ?>
<?php if ($monthError !== null): ?><div class="alert alert-warning" role="alert"><?= e($monthError) ?></div><?php endif; ?>
<?php if ($pastPeriodWarning): ?><div class="alert alert-warning" role="alert"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Generate kalender periode lampau menggunakan jadwal yang saat ini terpasang pada karyawan. Pastikan jadwal tersebut sesuai dengan jadwal historis sebelum melanjutkan.</div><?php endif; ?>
<section class="dashboard-panel mb-4" aria-labelledby="calendar-generate-title">
    <div class="panel-heading"><h2 class="panel-title mb-0" id="calendar-generate-title">Generate Kalender</h2></div>
    <div class="calendar-information"><i class="bi bi-info-circle" aria-hidden="true"></i><p>Kalender kerja adalah snapshot jadwal karyawan untuk menentukan hari kerja, hari libur, dan Alpha pada laporan. Snapshot tanggal lampau yang sudah tersedia tidak akan ditimpa.</p></div>
    <form class="calendar-filter-form" method="get" action="<?= e(url('/admin/work-calendar')) ?>">
        <div><label class="form-label" for="month">Bulan</label><input class="form-control" id="month" name="month" type="month" min="2000-01" max="2100-12" value="<?= e($selectedMonth) ?>" required></div>
        <div><label class="form-label" for="employee_id">Karyawan Aktif</label><select class="form-select" id="employee_id" name="employee_id" required><option value="">Pilih karyawan</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['id']) ?>"<?= (int) ($selectedEmployee['id'] ?? 0) === (int) $employee['id'] ? ' selected' : '' ?>><?= e($employee['name']) ?> (<?= e($employee['employee_code']) ?>)</option><?php endforeach; ?></select></div>
        <button class="btn btn-outline-primary" type="submit">Lihat Coverage</button>
    </form>
    <?php if ($selectedEmployee !== null): ?>
        <div class="calendar-generate-action"><div><strong><?= e($selectedEmployee['name']) ?></strong><span><?= e($selectedEmployee['employee_code']) ?> &middot; <?= e($selectedEmployee['work_schedule_name'] ?? 'Jadwal belum ditentukan') ?></span><?php if ($coverage !== null): ?><small>Coverage: <?= e($coverage['covered_days']) ?>/<?= e($coverage['total_days']) ?> tanggal<?= $coverage['complete'] ? ' (lengkap)' : ' (belum lengkap)' ?></small><?php endif; ?></div><form method="post" action="<?= e(url('/admin/work-calendar/generate')) ?>"><?= csrf_field() ?><input type="hidden" name="month" value="<?= e($selectedMonth) ?>"><input type="hidden" name="employee_id" value="<?= e($selectedEmployee['id']) ?>"><button class="btn btn-primary" type="submit"><i class="bi bi-calendar2-check me-1" aria-hidden="true"></i>Generate Kalender</button></form></div>
    <?php endif; ?>
</section>
<?php if ($employees === []): ?><section class="dashboard-panel"><div class="empty-state"><span class="empty-state-icon" aria-hidden="true"><i class="bi bi-people"></i></span><h3>Belum ada karyawan aktif.</h3><p>Kalender dapat dibuat setelah karyawan aktif dan jadwalnya tersedia.</p></div></section><?php endif; ?>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Kalender Kerja - Attendance App';
$activeNavigation = 'work-calendar';
require BASE_PATH . '/app/Views/layouts/app.php';
