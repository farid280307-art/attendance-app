<?php

declare(strict_types=1);

ob_start();
?>
<section class="dashboard-heading mb-4" aria-labelledby="attendance-title">
    <div>
        <p class="dashboard-eyebrow mb-1">Karyawan</p>
        <h1 class="dashboard-title" id="attendance-title">Absensi</h1>
        <p class="dashboard-date mb-0">Periksa apakah posisi Anda berada di dalam area lokasi kerja.</p>
    </div>
</section>

<section
    class="dashboard-panel location-check-panel mx-auto"
    id="attendanceLocationCheck"
    data-endpoint="<?= e(url('/attendance/location-check')) ?>"
    data-active-locations="<?= e($activeLocationCount) ?>"
    aria-labelledby="location-card-title"
>
    <input type="hidden" id="attendanceLocationToken" value="<?= e(csrf_token()) ?>">

    <div class="panel-heading">
        <div>
            <p class="panel-kicker mb-1">Verifikasi server</p>
            <h2 class="panel-title mb-0" id="location-card-title">Lokasi Anda</h2>
        </div>
        <span class="location-state-icon is-idle" id="locationStateIcon" aria-hidden="true">
            <i class="bi bi-geo-alt"></i>
        </span>
    </div>

    <div class="location-check-body">
        <div class="location-status-box is-idle" id="locationStatusBox" role="status" aria-live="polite">
            <span class="location-status-label">Status</span>
            <strong id="locationStatus">Lokasi belum diperiksa.</strong>
            <p id="locationMessage">Tekan tombol di bawah untuk memberikan izin dan memeriksa lokasi Anda.</p>
        </div>

        <dl class="location-result-grid d-none" id="locationResult">
            <div>
                <dt>Lokasi Kerja</dt>
                <dd id="nearestLocation">--</dd>
            </div>
            <div>
                <dt>Jarak</dt>
                <dd id="locationDistance">--</dd>
            </div>
            <div>
                <dt>Radius</dt>
                <dd id="locationRadius">--</dd>
            </div>
            <div>
                <dt>Akurasi GPS</dt>
                <dd id="locationAccuracy">--</dd>
            </div>
        </dl>

        <div class="location-phase-note d-none" id="locationPhaseNote">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <span>Lokasi memenuhi syarat absensi. Selfie akan dilakukan pada tahap berikutnya.</span>
        </div>

        <button class="btn btn-primary btn-lg w-100" id="checkLocationButton" type="button">
            <i class="bi bi-crosshair me-2" aria-hidden="true"></i>
            <span>Periksa Lokasi</span>
        </button>
        <p class="location-privacy-note mb-0">
            Koordinat hanya dikirim saat tombol ditekan. Keputusan radius dihitung ulang oleh server.
        </p>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Absensi - Attendance App';
$activeNavigation = 'attendance';
$pageScripts = ['js/attendance-location.js'];
require BASE_PATH . '/app/Views/layouts/app.php';
