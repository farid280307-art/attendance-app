<?php

declare(strict_types=1);

$success = is_string($success ?? null) ? $success : null;
$error = is_string($error ?? null) ? $error : null;
$monthError = is_string($monthError ?? null) ? $monthError : null;
$calendarCells = is_array($calendarCells ?? null) ? $calendarCells : [];
$calendarSummary = is_array($calendarSummary ?? null) ? $calendarSummary : null;
$calendarMonthLabel = is_string($calendarMonthLabel ?? null) ? $calendarMonthLabel : null;
$weekdayHeaders = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
$summaryItems = [
    ['key' => 'work', 'label' => 'Hari Kerja', 'class' => 'is-work'],
    ['key' => 'off', 'label' => 'Libur', 'class' => 'is-off'],
    ['key' => 'holiday', 'label' => 'Hari Libur', 'class' => 'is-holiday'],
    ['key' => 'missing', 'label' => 'Belum Digenerate', 'class' => 'is-missing'],
];

ob_start();
?>
<section class="management-heading mb-4">
    <div>
        <p class="dashboard-eyebrow mb-1">Kalender</p>
        <h1 class="dashboard-title">Kalender Kerja</h1>
        <p class="dashboard-date mb-0">Generate snapshot jadwal karyawan sebagai dasar penentuan Alpha.</p>
    </div>
</section>

<?php if ($success !== null): ?>
    <div class="alert alert-success app-alert" role="alert"><i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error !== null): ?>
    <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><?= e($error) ?></div>
<?php endif; ?>
<?php if ($monthError !== null): ?>
    <div class="alert alert-warning" role="alert"><?= e($monthError) ?></div>
<?php endif; ?>
<?php if ($pastPeriodWarning): ?>
    <div class="alert alert-warning" role="alert"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Generate kalender periode lampau menggunakan jadwal yang saat ini terpasang pada karyawan. Pastikan jadwal tersebut sesuai dengan jadwal historis sebelum melanjutkan.</div>
<?php endif; ?>

<section class="dashboard-panel mb-4" aria-labelledby="calendar-generate-title">
    <div class="panel-heading"><h2 class="panel-title mb-0" id="calendar-generate-title">Generate Kalender</h2></div>
    <div class="calendar-information"><i class="bi bi-info-circle" aria-hidden="true"></i><p>Kalender kerja adalah snapshot jadwal karyawan untuk menentukan hari kerja, hari libur, dan Alpha pada laporan. Snapshot tanggal lampau yang sudah tersedia tidak akan ditimpa.</p></div>
    <form class="calendar-filter-form" method="get" action="<?= e(url('/admin/work-calendar')) ?>">
        <div>
            <label class="form-label" for="month">Bulan</label>
            <input class="form-control" id="month" name="month" type="month" min="2000-01" max="2100-12" value="<?= e($selectedMonth) ?>" required>
        </div>
        <div>
            <label class="form-label" for="employee_id">Karyawan Aktif</label>
            <select class="form-select" id="employee_id" name="employee_id" required>
                <option value="">Pilih karyawan</option>
                <?php foreach ($employees as $employee): ?>
                    <option value="<?= e($employee['id']) ?>"<?= (int) ($selectedEmployee['id'] ?? 0) === (int) $employee['id'] ? ' selected' : '' ?>><?= e($employee['name']) ?> (<?= e($employee['employee_code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-outline-primary" type="submit">Lihat Kalender</button>
    </form>

    <?php if ($selectedEmployee !== null): ?>
        <div class="calendar-generate-action">
            <div>
                <strong><?= e($selectedEmployee['name']) ?></strong>
                <span><?= e($selectedEmployee['employee_code']) ?> &middot; <?= e($selectedEmployee['work_schedule_name'] ?? 'Jadwal belum ditentukan') ?></span>
                <?php if ($coverage !== null): ?>
                    <small>Coverage: <?= e($coverage['covered_days']) ?>/<?= e($coverage['total_days']) ?> tanggal<?= $coverage['complete'] ? ' (lengkap)' : '' ?></small>
                <?php endif; ?>
            </div>
            <form method="post" action="<?= e(url('/admin/work-calendar/generate')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="month" value="<?= e($selectedMonth) ?>">
                <input type="hidden" name="employee_id" value="<?= e($selectedEmployee['id']) ?>">
                <button class="btn btn-primary" id="calendar-generate-submit" type="submit"><i class="bi bi-calendar2-check me-1" aria-hidden="true"></i>Generate Kalender</button>
            </form>
        </div>
    <?php endif; ?>
</section>

<?php if ($selectedEmployee !== null && $calendarSummary !== null && $calendarMonthLabel !== null): ?>
    <section class="dashboard-panel work-calendar-visual" aria-labelledby="work-calendar-visual-title">
        <div class="panel-heading work-calendar-visual-heading">
            <div>
                <p class="panel-kicker mb-1">Snapshot Bulanan</p>
                <h2 class="panel-title mb-0" id="work-calendar-visual-title">Kalender <?= e($calendarMonthLabel) ?></h2>
            </div>
            <?php if ($coverage !== null): ?>
                <span class="badge <?= $coverage['complete'] ? 'text-bg-success' : 'text-bg-warning' ?>">Coverage <?= e($coverage['covered_days']) ?>/<?= e($coverage['total_days']) ?></span>
            <?php endif; ?>
        </div>

        <div class="work-calendar-content">
            <dl class="work-calendar-identity">
                <div><dt>Nama Karyawan</dt><dd><?= e($selectedEmployee['name']) ?></dd></div>
                <div><dt>Kode Karyawan</dt><dd><?= e($selectedEmployee['employee_code']) ?></dd></div>
                <div><dt>Nama Jadwal</dt><dd><?= e($selectedEmployee['work_schedule_name'] ?? 'Jadwal belum ditentukan') ?></dd></div>
                <div><dt>Coverage Snapshot</dt><dd><?= e($coverage['covered_days'] ?? 0) ?>/<?= e($coverage['total_days'] ?? count($calendarCells)) ?> tanggal<?= ($coverage['complete'] ?? false) ? ' (lengkap)' : '' ?></dd></div>
            </dl>

            <?php if (!($coverage['complete'] ?? false)): ?>
                <div class="alert alert-warning work-calendar-warning" role="alert">
                    <span><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Kalender belum lengkap. Generate kalender untuk melengkapi snapshot.</span>
                    <a class="btn btn-sm btn-warning" href="#calendar-generate-submit">Generate Kalender</a>
                </div>
            <?php endif; ?>

            <div class="work-calendar-summary" aria-label="Ringkasan kalender kerja">
                <?php foreach ($summaryItems as $item): ?>
                    <div class="work-calendar-summary-item <?= e($item['class']) ?>">
                        <strong><?= e($calendarSummary[$item['key']] ?? 0) ?></strong>
                        <span><?= e($item['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="work-calendar-legend" aria-label="Legenda status kalender">
                <?php foreach ($summaryItems as $item): ?>
                    <span><i class="work-calendar-legend-mark <?= e($item['class']) ?>" aria-hidden="true"></i><?= e($item['label']) ?></span>
                <?php endforeach; ?>
            </div>

            <div class="work-calendar-weekdays" aria-hidden="true">
                <?php foreach ($weekdayHeaders as $weekday): ?><span><?= e($weekday) ?></span><?php endforeach; ?>
            </div>
            <div class="work-calendar-grid" role="grid" aria-label="Kalender kerja <?= e($calendarMonthLabel) ?> untuk <?= e($selectedEmployee['name']) ?>">
                <?php foreach ($calendarCells as $day): ?>
                    <?php if ($day === null): ?>
                        <span class="work-calendar-day is-empty" role="gridcell" aria-hidden="true"></span>
                    <?php else: ?>
                        <article
                            class="work-calendar-day is-<?= e($day['day_type'] ?? 'missing') ?><?= $day['is_today'] ? ' is-today' : '' ?>"
                            role="gridcell"
                            aria-label="<?= e($day['accessible_description']) ?>"
                            title="<?= e($day['accessible_description']) ?>"
                        >
                            <div class="work-calendar-day-heading">
                                <strong><?= e($day['day']) ?></strong>
                                <?php if ($day['is_today']): ?><span>Hari ini</span><?php endif; ?>
                            </div>
                            <span class="work-calendar-day-status"><?= e($day['status_label']) ?></span>
                            <?php if ($day['day_type'] === 'holiday' && $day['holiday_name'] !== null): ?>
                                <small class="work-calendar-day-detail"><?= e($day['holiday_name']) ?></small>
                            <?php elseif ($day['day_type'] === 'work' && $day['schedule_name'] !== null): ?>
                                <small class="work-calendar-day-detail"><?= e($day['schedule_name']) ?></small>
                            <?php endif; ?>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($employees === []): ?>
    <section class="dashboard-panel"><div class="empty-state"><span class="empty-state-icon" aria-hidden="true"><i class="bi bi-people"></i></span><h3>Belum ada karyawan aktif.</h3><p>Kalender dapat dibuat setelah karyawan aktif dan jadwalnya tersedia.</p></div></section>
<?php endif; ?>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Kalender Kerja - Attendance App';
$activeNavigation = 'work-calendar';
require BASE_PATH . '/app/Views/layouts/app.php';
