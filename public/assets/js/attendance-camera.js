'use strict';

(() => {
    const card = document.getElementById('attendanceCameraCard');

    if (!card) return;

    const elements = {
        locationStep: document.getElementById('attendanceStepLocation'),
        selfieStep: document.getElementById('attendanceStepSelfie'),
        confirmationStep: document.getElementById('attendanceStepConfirmation'),
        cameraIcon: document.getElementById('cameraStateIcon'),
        cameraStatusBox: document.getElementById('cameraStatusBox'),
        cameraStatus: document.getElementById('cameraStatus'),
        cameraMessage: document.getElementById('cameraMessage'),
        openButton: document.getElementById('openCameraButton'),
        openButtonLabel: document.querySelector('#openCameraButton span'),
        liveRegion: document.getElementById('cameraLiveRegion'),
        video: document.getElementById('selfieVideo'),
        captureButton: document.getElementById('captureSelfieButton'),
        previewRegion: document.getElementById('cameraPreviewRegion'),
        preview: document.getElementById('selfiePreview'),
        retakeButton: document.getElementById('retakeSelfieButton'),
        useButton: document.getElementById('useSelfieButton'),
        confirmationCard: document.getElementById('attendanceConfirmationCard'),
        confirmationIcon: document.getElementById('confirmationStateIcon'),
        confirmationStatusBox: document.getElementById('confirmationStatusBox'),
        confirmationStatus: document.getElementById('confirmationStatus'),
        confirmationMessage: document.getElementById('confirmationMessage'),
        submitButton: document.getElementById('submitAttendanceButton'),
    };

    if (Object.values(elements).some((element) => !element)) return;

    const state = {
        locationVerified: false,
        cameraStream: null,
        capturedSelfieBlob: null,
        previewUrl: null,
        selfieConfirmed: false,
        openingCamera: false,
        version: 0,
    };

    const setStep = (element, status) => {
        element.classList.remove('is-active', 'is-complete', 'is-locked');
        element.classList.add(`is-${status}`);

        if (status === 'active') {
            element.setAttribute('aria-current', 'step');
        } else {
            element.removeAttribute('aria-current');
        }
    };

    const setCameraStatus = (tone, title, detail, icon) => {
        elements.cameraStatusBox.className = `camera-status-box is-${tone}`;
        elements.cameraStatus.textContent = title;
        elements.cameraMessage.textContent = detail;
        elements.cameraIcon.className = `location-state-icon is-${tone === 'locked' ? 'idle' : tone}`;
        elements.cameraIcon.innerHTML = `<i class="bi ${icon}"></i>`;
    };

    const setConfirmationStatus = (ready) => {
        elements.confirmationCard.classList.toggle('is-locked', !ready);
        elements.confirmationStatusBox.className = `camera-status-box is-${ready ? 'success' : 'locked'}`;
        elements.confirmationStatus.textContent = ready ? 'Absensi Siap Dikirim' : 'Belum siap';
        elements.confirmationMessage.textContent = ready
            ? 'Selfie siap digunakan. Pengiriman absensi belum tersedia pada tahap ini.'
            : 'Selesaikan verifikasi lokasi dan pengambilan selfie terlebih dahulu.';
        elements.confirmationIcon.className = `location-state-icon is-${ready ? 'success' : 'idle'}`;
        elements.confirmationIcon.innerHTML = `<i class="bi ${ready ? 'bi-check2-circle' : 'bi-lock'}"></i>`;
        elements.submitButton.disabled = true;
        setStep(elements.confirmationStep, ready ? 'active' : 'locked');
    };

    const stopStream = (stream) => {
        if (!stream) return;
        stream.getTracks().forEach((track) => track.stop());
    };

    const stopCamera = () => {
        stopStream(state.cameraStream);
        state.cameraStream = null;
        elements.video.pause();
        elements.video.srcObject = null;
        elements.captureButton.disabled = true;
    };

    const clearCapturedSelfie = () => {
        if (state.previewUrl) {
            URL.revokeObjectURL(state.previewUrl);
            state.previewUrl = null;
        }

        state.capturedSelfieBlob = null;
        state.selfieConfirmed = false;
        elements.preview.removeAttribute('src');
        elements.previewRegion.classList.add('d-none');
        elements.useButton.disabled = false;
        document.dispatchEvent(new CustomEvent('attendance:selfie-cleared'));
    };

    const resetWorkflow = (locationVerified) => {
        state.version += 1;
        state.locationVerified = locationVerified;
        state.openingCamera = false;
        stopCamera();
        clearCapturedSelfie();
        elements.liveRegion.classList.add('d-none');
        elements.openButton.classList.remove('d-none');
        elements.openButton.disabled = !locationVerified;
        elements.openButtonLabel.textContent = 'Buka Kamera';
        card.classList.toggle('is-locked', !locationVerified);
        setConfirmationStatus(false);

        if (locationVerified) {
            setStep(elements.locationStep, 'complete');
            setStep(elements.selfieStep, 'active');
            setCameraStatus('success', 'Kamera siap digunakan', 'Lokasi valid. Tekan Buka Kamera untuk mengambil selfie.', 'bi-camera-video');
        } else {
            setStep(elements.locationStep, 'active');
            setStep(elements.selfieStep, 'locked');
            setCameraStatus('locked', 'Terkunci', 'Verifikasi lokasi terlebih dahulu untuk membuka kamera.', 'bi-lock');
        }
    };

    const cameraErrorMessage = (errorName) => ({
        NotAllowedError: 'Izin kamera ditolak. Aktifkan izin kamera browser lalu coba lagi.',
        NotFoundError: 'Kamera tidak ditemukan pada perangkat ini.',
        NotReadableError: 'Kamera sedang digunakan aplikasi lain atau tidak dapat dibuka.',
        OverconstrainedError: 'Konfigurasi kamera tidak didukung perangkat ini.',
        SecurityError: 'Kamera hanya dapat digunakan pada koneksi yang aman.',
    })[errorName] || 'Kamera tidak dapat dibuka. Silakan coba lagi.';

    const showCameraError = (detail) => {
        stopCamera();
        elements.liveRegion.classList.add('d-none');
        elements.openButton.classList.remove('d-none');
        elements.openButton.disabled = !state.locationVerified;
        elements.openButtonLabel.textContent = 'Coba Lagi';
        setCameraStatus('danger', 'Kamera gagal dibuka', detail, 'bi-camera-video-off');
    };

    const openCamera = async () => {
        if (!state.locationVerified || state.openingCamera) return;

        if (!window.isSecureContext) {
            showCameraError('Kamera hanya dapat digunakan pada koneksi yang aman. Gunakan HTTPS atau localhost.');
            return;
        }

        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            showCameraError('Browser ini tidak mendukung akses kamera.');
            return;
        }

        const requestVersion = state.version;
        state.openingCamera = true;
        stopCamera();
        clearCapturedSelfie();
        setConfirmationStatus(false);
        elements.openButton.disabled = true;
        elements.openButtonLabel.textContent = 'Membuka kamera...';
        setCameraStatus('loading', 'Membuka kamera...', 'Izinkan browser mengakses kamera depan.', 'bi-arrow-repeat');

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'user' },
                    width: { ideal: 720, max: 1280 },
                    height: { ideal: 960, max: 1280 },
                },
                audio: false,
            });

            if (!state.locationVerified || requestVersion !== state.version) {
                stopStream(stream);
                return;
            }

            state.cameraStream = stream;
            elements.video.srcObject = stream;
            elements.liveRegion.classList.remove('d-none');
            elements.openButton.classList.add('d-none');

            const enableCapture = () => {
                if (state.cameraStream && elements.video.videoWidth > 0) {
                    elements.captureButton.disabled = false;
                }
            };

            elements.video.addEventListener('loadedmetadata', enableCapture, { once: true });
            await elements.video.play();
            enableCapture();
            setCameraStatus('success', 'Kamera aktif', 'Posisikan wajah di tengah kamera lalu ambil selfie.', 'bi-camera-video');
        } catch (error) {
            if (requestVersion === state.version && state.locationVerified) {
                showCameraError(cameraErrorMessage(error?.name));
            }
        } finally {
            if (requestVersion === state.version) {
                state.openingCamera = false;
            }
        }
    };

    const canvasToBlob = (canvas) => new Promise((resolve) => {
        canvas.toBlob(resolve, 'image/jpeg', 0.82);
    });

    const captureSelfie = async () => {
        if (!state.locationVerified || !state.cameraStream || elements.captureButton.disabled) return;

        const captureVersion = state.version;
        const sourceWidth = elements.video.videoWidth;
        const sourceHeight = elements.video.videoHeight;

        if (sourceWidth < 1 || sourceHeight < 1) {
            showCameraError('Frame kamera belum siap. Silakan buka kamera dan coba lagi.');
            return;
        }

        elements.captureButton.disabled = true;
        const longEdge = Math.max(sourceWidth, sourceHeight);
        const scale = Math.min(1, 960 / longEdge);
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(sourceWidth * scale));
        canvas.height = Math.max(1, Math.round(sourceHeight * scale));
        const context = canvas.getContext('2d');

        if (!context) {
            showCameraError('Selfie tidak dapat diproses. Silakan coba lagi.');
            return;
        }

        context.drawImage(elements.video, 0, 0, canvas.width, canvas.height);
        const blob = await canvasToBlob(canvas);

        if (!state.locationVerified || captureVersion !== state.version) return;

        if (!(blob instanceof Blob)) {
            showCameraError('Selfie tidak dapat diproses. Silakan coba lagi.');
            return;
        }

        stopCamera();
        clearCapturedSelfie();
        state.capturedSelfieBlob = blob;
        state.previewUrl = URL.createObjectURL(blob);
        elements.preview.src = state.previewUrl;
        elements.liveRegion.classList.add('d-none');
        elements.openButton.classList.add('d-none');
        elements.previewRegion.classList.remove('d-none');
        setCameraStatus('success', 'Selfie berhasil diambil', 'Periksa hasil foto, lalu gunakan selfie atau ambil ulang.', 'bi-image');
    };

    const confirmSelfie = () => {
        if (!state.locationVerified || !(state.capturedSelfieBlob instanceof Blob)) return;

        stopCamera();
        state.selfieConfirmed = true;
        elements.useButton.disabled = true;
        setStep(elements.selfieStep, 'complete');
        setConfirmationStatus(true);
        setCameraStatus('success', 'Selfie siap digunakan', 'Selfie tersimpan sementara di memory browser dan belum dikirim.', 'bi-check2-circle');

        // Phase 8 can consume this in-memory Blob and must send it with fresh coordinates via FormData.
        document.dispatchEvent(new CustomEvent('attendance:selfie-ready', {
            detail: { blob: state.capturedSelfieBlob },
        }));
    };

    elements.openButton.addEventListener('click', openCamera);
    elements.captureButton.addEventListener('click', captureSelfie);
    elements.retakeButton.addEventListener('click', () => {
        if (!state.locationVerified) return;
        clearCapturedSelfie();
        setStep(elements.selfieStep, 'active');
        setConfirmationStatus(false);
        openCamera();
    });
    elements.useButton.addEventListener('click', confirmSelfie);

    document.addEventListener('attendance:location-verified', () => resetWorkflow(true));
    document.addEventListener('attendance:location-invalid', () => resetWorkflow(false));

    const cleanUpPage = () => {
        state.version += 1;
        stopCamera();
        if (state.previewUrl) URL.revokeObjectURL(state.previewUrl);
        state.previewUrl = null;
        state.capturedSelfieBlob = null;
    };

    window.addEventListener('pagehide', cleanUpPage);
    window.addEventListener('beforeunload', cleanUpPage);
    resetWorkflow(false);
})();
