<?php

declare(strict_types=1);

$success = is_string($success ?? null) ? $success : null;
$summaryCards = [
    ['label' => 'Karyawan Aktif', 'value' => $summary['active_employees'], 'icon' => 'bi-people', 'tone' => 'primary'],
    ['label' => 'Sudah Absen Hari Ini', 'value' => $summary['checked_in_today'], 'icon' => 'bi-person-check', 'tone' => 'success'],
    ['label' => 'Terlambat Hari Ini', 'value' => $summary['late_today'], 'icon' => 'bi-clock-history', 'tone' => 'warning'],
    ['label' => 'Pengajuan Pending', 'value' => $summary['pending_leaves'], 'icon' => 'bi-file-earmark-text', 'tone' => 'info'],
];

ob_start();
?>
<section class="dashboard-heading mb-4" aria-labelledby="dashboard-title">
    <div>
        <p class="dashboard-eyebrow mb-1">Dashboard Admin</p>
        <h1 id="dashboard-title" class="dashboard-title">Selamat datang, <?= e($user['name']) ?></h1>
        <p class="dashboard-date mb-0"><i class="bi bi-calendar3 me-2" aria-hidden="true"></i><?= e($todayLabel) ?></p>
    </div>
</section>

<?php if ($success !== null): ?>
    <div class="alert alert-success app-alert" role="alert">
        <i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= e($success) ?>
    </div>
<?php endif; ?>

<section class="row g-3 mb-4" aria-label="Ringkasan hari ini">
    <?php foreach ($summaryCards as $card): ?>
        <div class="col-6 col-xl-3">
            <article class="summary-card h-100">
                <span class="summary-icon is-<?= e($card['tone']) ?>" aria-hidden="true">
                    <i class="bi <?= e($card['icon']) ?>"></i>
                </span>
                <div>
                    <p class="summary-value mb-1"><?= e($card['value']) ?></p>
                    <p class="summary-label mb-0"><?= e($card['label']) ?></p>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</section>

<div class="row g-4">
    <div class="col-12 col-xxl-7">
        <section class="dashboard-panel h-100" aria-labelledby="today-attendance-title">
            <div class="panel-heading">
                <div>
                    <p class="panel-kicker mb-1">Hari ini</p>
                    <h2 id="today-attendance-title" class="panel-title mb-0">Absensi Hari Ini</h2>
                </div>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/attendances')) ?>">Lihat Semua</a>
            </div>

            <?php if ($todayAttendances === []): ?>
                <div class="empty-state">
                    <span class="empty-state-icon" aria-hidden="true"><i class="bi bi-calendar2-check"></i></span>
                    <h3>Belum ada data</h3>
                    <p>Belum ada data absensi hari ini.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive d-none d-md-block">
                    <table class="table app-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Kode Karyawan</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayAttendances as $attendance): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($attendance['name']) ?></td>
                                    <td><?= e($attendance['employee_code']) ?></td>
                                    <td><?= e(format_attendance_time($attendance['check_in'])) ?></td>
                                    <td><?= e(format_attendance_time($attendance['check_out'])) ?></td>
                                    <td><span class="badge <?= e(attendance_status_class($attendance['status'])) ?>"><?= e(attendance_status_label($attendance['status'])) ?></span></td>
                                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/attendances/show?id=' . $attendance['id'])) ?>">Detail</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mobile-record-list d-md-none">
                    <?php foreach ($todayAttendances as $attendance): ?>
                        <article class="mobile-record">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div>
                                    <h3><?= e($attendance['name']) ?></h3>
                                    <p><?= e($attendance['employee_code']) ?></p>
                                </div>
                                <span class="badge <?= e(attendance_status_class($attendance['status'])) ?>"><?= e(attendance_status_label($attendance['status'])) ?></span>
                            </div>
                            <div class="mobile-record-meta">
                                <span><small>Masuk</small><strong><?= e(format_attendance_time($attendance['check_in'])) ?></strong></span>
                                <span><small>Pulang</small><strong><?= e(format_attendance_time($attendance['check_out'])) ?></strong></span>
                            </div>
                            <a class="btn btn-sm btn-outline-primary mt-3" href="<?= e(url('/admin/attendances/show?id=' . $attendance['id'])) ?>">Lihat Detail</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-12 col-xxl-5">
        <section class="dashboard-panel h-100" aria-labelledby="recent-leave-title">
            <div class="panel-heading">
                <div>
                    <p class="panel-kicker mb-1">Terbaru</p>
                    <h2 id="recent-leave-title" class="panel-title mb-0">Pengajuan Terbaru</h2>
                </div>
                <span class="panel-count"><?= e(count($recentLeaveRequests)) ?> data</span>
            </div>

            <?php if ($recentLeaveRequests === []): ?>
                <div class="empty-state">
                    <span class="empty-state-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                    <h3>Belum ada pengajuan</h3>
                    <p>Pengajuan terbaru akan tampil di sini.</p>
                </div>
            <?php else: ?>
                <div class="request-list">
                    <?php foreach ($recentLeaveRequests as $request): ?>
                        <article class="request-item">
                            <div class="request-copy">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <h3 class="mb-0"><?= e($request['name']) ?></h3>
                                    <span class="badge <?= e(leave_status_class($request['status'])) ?>"><?= e(leave_status_label($request['status'])) ?></span>
                                </div>
                                <p class="mb-0"><?= e(leave_type_label($request['type'])) ?> · <?= e(indonesian_date_range($request['start_date'], $request['end_date'])) ?></p>
                            </div>
                            <span class="request-type-icon" aria-hidden="true"><i class="bi bi-file-earmark"></i></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Dashboard Admin - Attendance App';
require BASE_PATH . '/app/Views/layouts/app.php';
