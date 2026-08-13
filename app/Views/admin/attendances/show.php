<?php

declare(strict_types=1);

$isLate = $attendance['status'] === 'late';
$attendanceDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $attendance['attendance_date'], new DateTimeZone('Asia/Jakarta'));
$fullDate = $attendanceDate === false ? '--' : indonesian_date($attendanceDate);
$meters = static function (mixed $value, string $empty = '--'): string {
    if ($value === null || $value === '') {
        return $empty;
    }

    return number_format((float) $value, 1, ',', '.') . ' meter';
};
$coordinate = static function (mixed $value): string {
    return $value === null || $value === '' ? '--' : number_format((float) $value, 7, '.', '');
};

ob_start();
?>
<section class="management-heading mb-4" aria-labelledby="attendance-detail-title"><div><a class="small text-decoration-none" href="<?= e(url('/admin/attendances')) ?>"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Kembali ke daftar</a><h1 class="dashboard-title mt-2" id="attendance-detail-title">Detail Absensi</h1><p class="dashboard-date mb-0">Data lokasi dan selfie merupakan bukti verifikasi absensi.</p></div></section>

<section class="attendance-evidence-header mb-4" aria-label="Ringkasan absensi">
    <div class="attendance-evidence-avatar" aria-hidden="true"><i class="bi bi-person"></i></div>
    <div class="attendance-evidence-identity"><small>Karyawan</small><strong><?= e($attendance['name']) ?></strong><span><?= e($attendance['employee_code']) ?> &middot; <?= e($attendance['username']) ?></span></div>
    <div class="attendance-evidence-date"><small>Tanggal</small><strong><?= e($fullDate) ?></strong></div>
    <div class="attendance-evidence-status"><span class="badge <?= e(attendance_status_class($attendance['status'])) ?>"><?= e(attendance_status_label($attendance['status'])) ?></span><?php if ($isLate): ?><small>Terlambat <?= e((int) $attendance['late_minutes']) ?> menit</small><?php endif; ?></div>
</section>

<div class="attendance-evidence-grid">
    <section class="dashboard-panel" aria-labelledby="check-in-evidence-title">
        <div class="panel-heading"><div><p class="panel-kicker mb-1">Bukti kehadiran</p><h2 class="panel-title mb-0" id="check-in-evidence-title">Absensi Masuk</h2></div><strong class="attendance-evidence-time"><?= e(format_attendance_time($attendance['check_in'])) ?></strong></div>
        <div class="attendance-evidence-body">
            <?php if ($attendance['has_check_in_photo']): ?><div class="attendance-selfie-frame"><img src="<?= e(url('/admin/attendances/photo?id=' . $attendance['id'] . '&type=check_in')) ?>" alt="Selfie Absensi Masuk <?= e($attendance['name']) ?>"></div><?php else: ?><div class="attendance-photo-empty"><i class="bi bi-image" aria-hidden="true"></i><span>Selfie belum tersedia.</span></div><?php endif; ?>
            <dl class="attendance-evidence-data">
                <div><dt>Lokasi Kerja Absensi Masuk</dt><dd><?= e($attendance['work_location_name'] ?? 'Lokasi tidak tersedia') ?></dd></div>
                <div><dt>Radius Lokasi</dt><dd><?= e($meters($attendance['radius_meters'] ?? null)) ?></dd></div>
                <div><dt>Jarak</dt><dd><?= e($meters($attendance['check_in_distance'])) ?></dd></div>
                <div><dt>Akurasi GPS</dt><dd><?= $attendance['check_in_accuracy'] === null ? '--' : '±' . e($meters($attendance['check_in_accuracy'])) ?></dd></div>
                <div><dt>Latitude</dt><dd><?= e($coordinate($attendance['check_in_latitude'])) ?></dd></div>
                <div><dt>Longitude</dt><dd><?= e($coordinate($attendance['check_in_longitude'])) ?></dd></div>
            </dl>
        </div>
    </section>

    <section class="dashboard-panel" aria-labelledby="check-out-evidence-title">
        <div class="panel-heading"><div><p class="panel-kicker mb-1">Bukti kepulangan</p><h2 class="panel-title mb-0" id="check-out-evidence-title">Absensi Pulang</h2></div><strong class="attendance-evidence-time"><?= e(format_attendance_time($attendance['check_out'])) ?></strong></div>
        <div class="attendance-evidence-body">
            <?php if ($attendance['has_check_out_photo']): ?><div class="attendance-selfie-frame"><img src="<?= e(url('/admin/attendances/photo?id=' . $attendance['id'] . '&type=check_out')) ?>" alt="Selfie Absensi Pulang <?= e($attendance['name']) ?>"></div><?php else: ?><div class="attendance-photo-empty"><i class="bi bi-image" aria-hidden="true"></i><span>Selfie belum tersedia.</span></div><?php endif; ?>
            <dl class="attendance-evidence-data">
                <div><dt>Jarak Validasi</dt><dd><?= e($meters($attendance['check_out_distance'])) ?></dd></div>
                <div><dt>Akurasi GPS</dt><dd><?= $attendance['check_out_accuracy'] === null ? '--' : '±' . e($meters($attendance['check_out_accuracy'])) ?></dd></div>
                <div><dt>Latitude</dt><dd><?= e($coordinate($attendance['check_out_latitude'])) ?></dd></div>
                <div><dt>Longitude</dt><dd><?= e($coordinate($attendance['check_out_longitude'])) ?></dd></div>
            </dl>
            <p class="attendance-checkout-limitation mb-0"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Nama lokasi Absensi Pulang tidak tersimpan pada skema saat ini. Data yang tersedia adalah jarak validasi, akurasi, dan koordinat saat Absensi Pulang.</span></p>
        </div>
    </section>
</div>

<section class="attendance-record-meta mt-4" aria-label="Metadata catatan"><span>Dibuat: <?= e(format_app_datetime($attendance['created_at'])) ?></span><span>Terakhir diperbarui: <?= e(format_app_datetime($attendance['updated_at'])) ?></span></section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Detail Absensi - Attendance App';
$activeNavigation = 'admin-attendances';
require BASE_PATH . '/app/Views/layouts/app.php';
