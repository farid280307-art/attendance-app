<?php

declare(strict_types=1);

$success = is_string($success ?? null) ? $success : null;
$attendance = $todayStatus['attendance'];
$attendanceStateClass = [
    'not_checked_in' => 'is-neutral',
    'checked_in' => 'is-progress',
    'completed' => 'is-complete',
][$todayStatus['state']] ?? 'is-neutral';
$attendanceActionLabel = match ($todayStatus['state']) {
    'checked_in' => 'Absensi Pulang',
    'completed' => 'Lihat Absensi Hari Ini',
    default => 'Absensi Masuk',
};
$attendanceActionHelp = match ($todayStatus['state']) {
    'checked_in' => 'Selesaikan Absensi Pulang setelah jam kerja berakhir.',
    'completed' => 'Absensi Masuk dan Pulang hari ini sudah tercatat.',
    default => 'Verifikasi lokasi dan selfie untuk mencatat kehadiran.',
};
$monthlyCards = [
    ['label' => 'Hadir', 'value' => $monthlySummary['present'], 'icon' => 'bi-check-circle', 'tone' => 'success'],
    ['label' => 'Terlambat', 'value' => $monthlySummary['late'], 'icon' => 'bi-clock-history', 'tone' => 'warning'],
    ['label' => 'Cuti', 'value' => $monthlySummary['leave'], 'icon' => 'bi-calendar2-week', 'tone' => 'primary'],
    ['label' => 'Sakit', 'value' => $monthlySummary['sick'], 'icon' => 'bi-bandaid', 'tone' => 'danger'],
    ['label' => 'Izin', 'value' => $monthlySummary['permission'], 'icon' => 'bi-file-earmark-check', 'tone' => 'info'],
];

ob_start();
?>
<section class="dashboard-heading mb-4" aria-labelledby="dashboard-title">
    <div>
        <p class="dashboard-eyebrow mb-1">Dashboard Karyawan</p>
        <h1 id="dashboard-title" class="dashboard-title"><?= e($greeting) ?>, <?= e($user['name']) ?></h1>
        <p class="dashboard-date mb-0"><i class="bi bi-calendar3 me-2" aria-hidden="true"></i><?= e($todayLabel) ?></p>
    </div>
</section>

<?php if ($success !== null): ?>
    <div class="alert alert-success app-alert" role="alert">
        <i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= e($success) ?>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-12 col-xl-7">
        <section class="attendance-status-card <?= e($attendanceStateClass) ?> h-100" aria-labelledby="today-status-title">
            <div class="attendance-status-heading">
                <div>
                    <p class="panel-kicker mb-1">Status Hari Ini</p>
                    <h2 id="today-status-title" class="attendance-status-title mb-0"><?= e($todayStatus['label']) ?></h2>
                </div>
                <span class="attendance-status-icon" aria-hidden="true"><i class="bi bi-fingerprint"></i></span>
            </div>

            <?php if ($attendance !== null && $attendance['check_in'] !== null): ?>
                <span class="badge <?= e(attendance_status_class($attendance['status'])) ?> mb-4"><?= e(attendance_status_label($attendance['status'])) ?></span>
            <?php endif; ?>

            <div class="attendance-times">
                <div>
                    <span>Jam Masuk</span>
                    <strong><?= e(format_attendance_time($attendance['check_in'] ?? null)) ?></strong>
                </div>
                <div>
                    <span>Jam Pulang</span>
                    <strong><?= e(format_attendance_time($attendance['check_out'] ?? null)) ?></strong>
                </div>
            </div>

            <div class="attendance-action">
                <a class="btn btn-primary" href="<?= e(url('/attendance')) ?>"><?= e($attendanceActionLabel) ?></a>
                <p class="mb-0"><?= e($attendanceActionHelp) ?></p>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-5">
        <section class="dashboard-panel h-100" aria-labelledby="monthly-summary-title">
            <div class="panel-heading">
                <div>
                    <p class="panel-kicker mb-1"><?= e($monthLabel) ?></p>
                    <h2 id="monthly-summary-title" class="panel-title mb-0">Ringkasan Bulan Ini</h2>
                </div>
            </div>
            <div class="monthly-summary-grid">
                <?php foreach ($monthlyCards as $card): ?>
                    <article class="monthly-summary-item">
                        <span class="monthly-summary-icon is-<?= e($card['tone']) ?>" aria-hidden="true"><i class="bi <?= e($card['icon']) ?>"></i></span>
                        <span>
                            <strong><?= e($card['value']) ?></strong>
                            <small><?= e($card['label']) ?></small>
                        </span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<section class="dashboard-panel" aria-labelledby="recent-attendance-title">
    <div class="panel-heading">
        <div>
            <p class="panel-kicker mb-1">Aktivitas Anda</p>
            <h2 id="recent-attendance-title" class="panel-title mb-0">Riwayat Absensi Terbaru</h2>
        </div>
        <span class="panel-count"><?= e(count($recentAttendances)) ?> data</span>
    </div>

    <?php if ($recentAttendances === []): ?>
        <div class="empty-state">
            <span class="empty-state-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
            <h3>Belum ada riwayat</h3>
            <p>Belum ada riwayat absensi.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive d-none d-md-block">
            <table class="table app-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Masuk</th>
                        <th>Pulang</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAttendances as $recentAttendance): ?>
                        <tr>
                            <td class="fw-semibold"><?= e(indonesian_short_date($recentAttendance['attendance_date'])) ?></td>
                            <td><?= e(format_attendance_time($recentAttendance['check_in'])) ?></td>
                            <td><?= e(format_attendance_time($recentAttendance['check_out'])) ?></td>
                            <td><span class="badge <?= e(attendance_status_class($recentAttendance['status'])) ?>"><?= e(attendance_status_label($recentAttendance['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mobile-record-list d-md-none">
            <?php foreach ($recentAttendances as $recentAttendance): ?>
                <article class="mobile-record">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <h3><?= e(indonesian_short_date($recentAttendance['attendance_date'])) ?></h3>
                        <span class="badge <?= e(attendance_status_class($recentAttendance['status'])) ?>"><?= e(attendance_status_label($recentAttendance['status'])) ?></span>
                    </div>
                    <div class="mobile-record-meta">
                        <span><small>Masuk</small><strong><?= e(format_attendance_time($recentAttendance['check_in'])) ?></strong></span>
                        <span><small>Pulang</small><strong><?= e(format_attendance_time($recentAttendance['check_out'])) ?></strong></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Dashboard Karyawan - Attendance App';
require BASE_PATH . '/app/Views/layouts/app.php';
