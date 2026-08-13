<?php

declare(strict_types=1);

$success = is_string($success ?? null) ? $success : null;
$error = is_string($error ?? null) ? $error : null;
$shiftFilterError = is_string($shiftFilterError ?? null) ? $shiftFilterError : null;
$weekdayLabels = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];
$workingDayText = static function (array $days) use ($weekdayLabels): string {
    if ($days === []) {
        return 'Hari kerja belum dikonfigurasi.';
    }

    return implode(', ', array_map(static fn (int $day): string => $weekdayLabels[$day] ?? '-', $days));
};
ob_start();
?>
<section class="management-heading mb-4"><div><p class="dashboard-eyebrow mb-1">Master Data</p><h1 class="dashboard-title">Jadwal Kerja</h1><p class="dashboard-date mb-0">Kelola jam, hari kerja, dan penugasan jadwal karyawan.</p></div><a class="btn btn-primary" href="<?= e(url('/admin/work-schedules/create')) ?>"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Jadwal</a></section>
<?php if ($success !== null): ?><div class="alert alert-success app-alert" role="alert"><i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= e($success) ?></div><?php endif; ?>
<?php if ($error !== null): ?><div class="alert alert-danger app-alert" role="alert"><i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><?= e($error) ?></div><?php endif; ?>
<?php if ($shiftFilterError !== null): ?><div class="alert alert-warning app-alert" role="alert"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i><?= e($shiftFilterError) ?></div><?php endif; ?>

<section class="dashboard-panel mb-4" aria-labelledby="schedules-title">
    <div class="panel-heading"><h2 class="panel-title mb-0" id="schedules-title">Daftar Jadwal</h2><span class="panel-count"><?= e(count($schedules)) ?> data</span></div>
    <?php if ($schedules === []): ?>
        <div class="empty-state"><span class="empty-state-icon" aria-hidden="true"><i class="bi bi-clock"></i></span><h3>Belum ada jadwal kerja.</h3><p>Tambahkan jadwal sebelum menugaskannya kepada karyawan.</p></div>
    <?php else: ?>
        <div class="table-responsive d-none d-md-block"><table class="table app-table align-middle mb-0"><thead><tr><th>Nama Jadwal</th><th>Jam Kerja</th><th>Hari Kerja</th><th>Toleransi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
        <?php foreach ($schedules as $schedule): ?><tr><td class="fw-semibold"><?= e($schedule['name']) ?></td><td><?= e(substr((string) $schedule['start_time'], 0, 5)) ?>–<?= e(substr((string) $schedule['end_time'], 0, 5)) ?></td><td class="schedule-days-cell<?= $schedule['working_days'] === [] ? ' text-warning-emphasis' : '' ?>"><?= e($workingDayText($schedule['working_days'])) ?></td><td><?= e($schedule['late_tolerance_minutes']) ?> menit</td><td><span class="badge <?= (int) $schedule['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $schedule['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td><td><div class="record-actions justify-content-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/work-schedules/edit?id=' . $schedule['id'])) ?>">Edit</a><form method="post" action="<?= e(url('/admin/work-schedules/toggle')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($schedule['id']) ?>"><button class="btn btn-sm <?= (int) $schedule['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= (int) $schedule['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></div></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <div class="mobile-record-list d-md-none"><?php foreach ($schedules as $schedule): ?><article class="mobile-record"><div class="d-flex justify-content-between gap-3"><div><h3><?= e($schedule['name']) ?></h3><p><?= e(substr((string) $schedule['start_time'], 0, 5)) ?>–<?= e(substr((string) $schedule['end_time'], 0, 5)) ?></p></div><span class="badge align-self-start <?= (int) $schedule['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $schedule['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></div><p class="schedule-mobile-days<?= $schedule['working_days'] === [] ? ' text-warning-emphasis' : '' ?>"><small>Hari kerja</small><strong><?= e($workingDayText($schedule['working_days'])) ?></strong></p><p class="mb-3"><small>Toleransi terlambat</small><br><strong><?= e($schedule['late_tolerance_minutes']) ?> menit</strong></p><div class="record-actions"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/work-schedules/edit?id=' . $schedule['id'])) ?>">Edit</a><form method="post" action="<?= e(url('/admin/work-schedules/toggle')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($schedule['id']) ?>"><button class="btn btn-sm <?= (int) $schedule['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= (int) $schedule['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></div></article><?php endforeach; ?></div>
    <?php endif; ?>
</section>

<section class="dashboard-panel" aria-labelledby="assignment-title">
    <div class="panel-heading schedule-assignment-heading"><div><p class="panel-kicker mb-1">Karyawan aktif</p><h2 class="panel-title mb-0" id="assignment-title">Penugasan Jadwal Karyawan</h2></div><span class="panel-count"><?php if ($selectedShift === 'all'): ?><?= e($allEmployeeCount) ?> karyawan<?php else: ?><?= e($filteredEmployeeCount) ?> dari <?= e($allEmployeeCount) ?> karyawan<?php endif; ?></span></div>
    <div class="schedule-assignment-filter">
        <form method="get" action="<?= e(url('/admin/work-schedules')) ?>">
            <div>
                <label class="form-label" for="shift">Filter Shift</label>
                <select class="form-select" id="shift" name="shift">
                    <option value="all"<?= $selectedShift === 'all' ? ' selected' : '' ?>>Semua Shift</option>
                    <option value="unassigned"<?= $selectedShift === 'unassigned' ? ' selected' : '' ?>>Belum Diberi Shift</option>
                    <?php foreach ($schedules as $schedule): ?>
                        <option value="<?= e($schedule['id']) ?>"<?= $selectedShift === (string) $schedule['id'] ? ' selected' : '' ?>><?= e($schedule['name']) ?><?= (int) $schedule['is_active'] !== 1 ? ' (Nonaktif)' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-outline-primary" type="submit">Terapkan</button>
            <?php if ($selectedShift !== 'all'): ?><a class="btn btn-outline-secondary" href="<?= e(url('/admin/work-schedules?shift=all')) ?>">Reset</a><?php endif; ?>
        </form>
        <p class="mb-0"><?php if ($selectedShift === 'all'): ?>Menampilkan seluruh <?= e($allEmployeeCount) ?> karyawan aktif.<?php else: ?>Menampilkan <?= e($filteredEmployeeCount) ?> dari <?= e($allEmployeeCount) ?> karyawan aktif pada filter <strong><?= e($selectedShiftLabel) ?></strong>.<?php endif; ?></p>
    </div>
    <?php if ($allEmployeeCount === 0): ?>
        <div class="empty-state"><span class="empty-state-icon" aria-hidden="true"><i class="bi bi-people"></i></span><h3>Belum ada karyawan aktif.</h3><p>Penugasan jadwal akan tersedia setelah data karyawan aktif tersedia.</p></div>
    <?php elseif ($employees === []): ?>
        <div class="empty-state"><span class="empty-state-icon" aria-hidden="true"><i class="bi bi-funnel"></i></span><h3>Tidak ada karyawan pada filter ini.</h3><p><?php if ($selectedShift === 'unassigned'): ?>Tidak ada karyawan yang belum diberi shift.<?php else: ?>Tidak ada karyawan pada <?= e($selectedShiftLabel) ?>.<?php endif; ?></p><a class="btn btn-outline-primary mt-3" href="<?= e(url('/admin/work-schedules?shift=all')) ?>">Tampilkan Semua</a></div>
    <?php else: ?>
        <div class="assignment-list">
        <?php foreach ($employees as $employee): ?>
            <article class="assignment-item">
                <div class="assignment-identity"><h3><?= e($employee['name']) ?></h3><p><?= e($employee['employee_code']) ?></p><small>Jadwal saat ini: <strong><?= e($employee['work_schedule_name'] ?? 'Belum ditentukan') ?></strong><?php if ($employee['work_schedule_id'] !== null && (int) ($employee['work_schedule_is_active'] ?? 0) !== 1): ?> (nonaktif)<?php endif; ?></small></div>
                <form class="assignment-form" method="post" action="<?= e(url('/admin/work-schedules/assign')) ?>"><?= csrf_field() ?><input type="hidden" name="employee_id" value="<?= e($employee['id']) ?>"><input type="hidden" name="return_shift" value="<?= e($selectedShift) ?>"><label class="visually-hidden" for="schedule-<?= e($employee['id']) ?>">Ubah jadwal <?= e($employee['name']) ?></label><select class="form-select" id="schedule-<?= e($employee['id']) ?>" name="work_schedule_id" required<?= $activeSchedules === [] ? ' disabled' : '' ?>><option value="">Pilih jadwal aktif</option><?php foreach ($activeSchedules as $activeSchedule): ?><option value="<?= e($activeSchedule['id']) ?>"<?= (int) ($employee['work_schedule_id'] ?? 0) === (int) $activeSchedule['id'] ? ' selected' : '' ?>><?= e($activeSchedule['name']) ?> (<?= e(substr((string) $activeSchedule['start_time'], 0, 5)) ?>–<?= e(substr((string) $activeSchedule['end_time'], 0, 5)) ?>)</option><?php endforeach; ?></select><button class="btn btn-primary" type="submit"<?= $activeSchedules === [] ? ' disabled' : '' ?>>Simpan</button></form>
            </article>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Jadwal Kerja - Attendance App';
$activeNavigation = 'work-schedules';
require BASE_PATH . '/app/Views/layouts/app.php';
