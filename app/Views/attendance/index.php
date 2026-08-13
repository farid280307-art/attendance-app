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

<div class="attendance-flow mx-auto">
<ol class="attendance-steps" aria-label="Tahapan absensi">
    <li class="attendance-step is-active" id="attendanceStepLocation" aria-current="step">
        <span class="attendance-step-number">1</span>
        <span><strong>Lokasi</strong><small>Verifikasi posisi</small></span>
    </li>
    <li class="attendance-step is-locked" id="attendanceStepSelfie">
        <span class="attendance-step-number">2</span>
        <span><strong>Selfie</strong><small>Ambil foto</small></span>
    </li>
    <li class="attendance-step is-locked" id="attendanceStepConfirmation">
        <span class="attendance-step-number">3</span>
        <span><strong>Konfirmasi</strong><small>Belum tersedia</small></span>
    </li>
</ol>

<section
    class="dashboard-panel location-check-panel"
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
            <span>Lokasi memenuhi syarat. Lanjutkan dengan mengambil selfie kehadiran.</span>
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

<section class="dashboard-panel attendance-flow-card is-locked" id="attendanceCameraCard" aria-labelledby="camera-card-title">
    <div class="panel-heading">
        <div>
            <p class="panel-kicker mb-1">Langkah 2</p>
            <h2 class="panel-title mb-0" id="camera-card-title">Selfie Kehadiran</h2>
        </div>
        <span class="location-state-icon is-idle" id="cameraStateIcon" aria-hidden="true">
            <i class="bi bi-lock"></i>
        </span>
    </div>

    <div class="camera-step-body">
        <div class="camera-status-box is-locked" id="cameraStatusBox" role="status" aria-live="polite">
            <strong id="cameraStatus">Terkunci</strong>
            <p id="cameraMessage">Verifikasi lokasi terlebih dahulu untuk membuka kamera.</p>
        </div>

        <div class="camera-live-region d-none" id="cameraLiveRegion">
            <div class="selfie-camera-frame">
                <video id="selfieVideo" autoplay playsinline muted aria-label="Preview langsung kamera selfie"></video>
                <div class="selfie-guide" aria-hidden="true"><span></span></div>
                <p class="selfie-guide-text">Posisikan wajah di tengah kamera.</p>
            </div>
            <button class="btn btn-primary btn-lg w-100 mt-3" id="captureSelfieButton" type="button" disabled>
                <i class="bi bi-camera me-2" aria-hidden="true"></i>Ambil Selfie
            </button>
        </div>

        <div class="camera-preview-region d-none" id="cameraPreviewRegion">
            <div class="selfie-camera-frame">
                <img id="selfiePreview" alt="Preview selfie kehadiran">
            </div>
            <p class="camera-capture-success"><i class="bi bi-check-circle-fill" aria-hidden="true"></i>Selfie berhasil diambil.</p>
            <div class="camera-preview-actions">
                <button class="btn btn-outline-secondary" id="retakeSelfieButton" type="button">
                    <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Foto Ulang
                </button>
                <button class="btn btn-primary" id="useSelfieButton" type="button">
                    <i class="bi bi-check2 me-1" aria-hidden="true"></i>Gunakan Selfie
                </button>
            </div>
        </div>

        <button class="btn btn-primary btn-lg w-100 mt-3" id="openCameraButton" type="button" disabled>
            <i class="bi bi-camera-video me-2" aria-hidden="true"></i><span>Buka Kamera</span>
        </button>

        <div class="camera-privacy-note">
            <i class="bi bi-shield-check" aria-hidden="true"></i>
            <span>Selfie digunakan sebagai bukti kehadiran. Foto belum dikirim sampai absensi dikonfirmasi.</span>
        </div>
    </div>
</section>

<section class="dashboard-panel attendance-flow-card is-locked" id="attendanceConfirmationCard" aria-labelledby="confirmation-card-title">
    <div class="panel-heading">
        <div>
            <p class="panel-kicker mb-1">Langkah 3</p>
            <h2 class="panel-title mb-0" id="confirmation-card-title">Konfirmasi Absensi</h2>
        </div>
        <span class="location-state-icon is-idle" id="confirmationStateIcon" aria-hidden="true">
            <i class="bi bi-lock"></i>
        </span>
    </div>
    <div class="camera-step-body">
        <div class="camera-status-box is-locked" id="confirmationStatusBox" role="status" aria-live="polite">
            <strong id="confirmationStatus">Belum siap</strong>
            <p id="confirmationMessage">Selesaikan verifikasi lokasi dan pengambilan selfie terlebih dahulu.</p>
        </div>
        <button class="btn btn-primary btn-lg w-100 mt-3" id="submitAttendanceButton" type="button" disabled>
            Kirim Absensi
        </button>
        <p class="location-privacy-note mb-0">Pengiriman absensi akan diaktifkan pada tahap berikutnya.</p>
    </div>
</section>
</div>
<?php
$content = (string) ob_get_clean();
$pageTitle = 'Absensi - Attendance App';
$activeNavigation = 'attendance';
$pageScripts = ['js/attendance-location.js', 'js/attendance-camera.js'];
require BASE_PATH . '/app/Views/layouts/app.php';
