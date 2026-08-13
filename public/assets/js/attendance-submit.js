'use strict';

(() => {
    const container = document.getElementById('attendanceSubmission');

    if (!container) return;

    const submitButton = document.getElementById('submitAttendanceButton');
    const submitLabel = submitButton?.querySelector('span');
    const token = document.getElementById('attendanceLocationToken');
    const errorBox = document.getElementById('attendanceSubmitError');

    if (!submitButton || !submitLabel || !token || !errorBox) return;

    const state = {
        selfieBlob: null,
        submitting: false,
        defaultLabel: submitLabel.textContent,
    };

    const showError = (message) => {
        errorBox.textContent = message;
        errorBox.classList.remove('d-none');
    };

    const clearError = () => {
        errorBox.textContent = '';
        errorBox.classList.add('d-none');
    };

    const restoreSubmit = () => {
        state.submitting = false;
        submitButton.disabled = !(state.selfieBlob instanceof Blob);
        submitLabel.textContent = state.defaultLabel;
    };

    const resetSecureWorkflow = () => {
        state.selfieBlob = null;
        state.submitting = false;
        submitButton.disabled = true;
        submitLabel.textContent = state.defaultLabel;
        document.dispatchEvent(new CustomEvent('attendance:location-invalid', {
            detail: { valid: false },
        }));
    };

    const freshLocation = () => new Promise((resolve, reject) => {
        if (!window.isSecureContext) {
            reject(new Error('Koneksi aman diperlukan untuk mengambil lokasi.'));
            return;
        }

        if (!('geolocation' in navigator)) {
            reject(new Error('Browser ini tidak mendukung fitur lokasi.'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => resolve(position.coords),
            reject,
            {
                enableHighAccuracy: true,
                timeout: 30000,
                maximumAge: 0,
            }
        );
    });

    const gpsErrorMessage = (error) => ({
        1: 'Izin lokasi diperlukan untuk mengirim absensi.',
        2: 'Lokasi tidak dapat ditemukan. Pastikan GPS aktif lalu coba lagi.',
        3: 'Pengambilan lokasi terlalu lama. Silakan coba lagi.',
    })[error?.code] || error?.message || 'Lokasi baru tidak dapat diambil. Silakan coba lagi.';

    const submitAttendance = async () => {
        if (state.submitting || !(state.selfieBlob instanceof Blob)) return;

        state.submitting = true;
        submitButton.disabled = true;
        clearError();
        submitLabel.textContent = 'Memverifikasi lokasi...';

        let coordinates;

        try {
            coordinates = await freshLocation();
        } catch (error) {
            showError(gpsErrorMessage(error));
            restoreSubmit();
            return;
        }

        submitLabel.textContent = 'Mengirim absensi...';
        const body = new FormData();
        body.append('_token', token.value);
        body.append('latitude', String(coordinates.latitude));
        body.append('longitude', String(coordinates.longitude));
        body.append('accuracy', String(coordinates.accuracy));
        body.append('selfie', state.selfieBlob, 'selfie.jpg');

        try {
            const response = await fetch(container.dataset.submitEndpoint, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();

            if (!response.ok || payload.success !== true) {
                showError(payload.message || 'Absensi tidak dapat diproses. Silakan coba lagi.');

                if (payload.reset_workflow === true) {
                    resetSecureWorkflow();
                } else {
                    restoreSubmit();
                }
                return;
            }

            state.selfieBlob = null;
            submitLabel.textContent = payload.message || 'Absensi berhasil.';
            window.location.assign(payload.redirect_url || window.location.href);
        } catch (error) {
            showError('Terjadi gangguan saat mengirim absensi. Silakan coba lagi.');
            restoreSubmit();
        }
    };

    document.addEventListener('attendance:selfie-ready', (event) => {
        const blob = event.detail?.blob;
        state.selfieBlob = blob instanceof Blob ? blob : null;
        submitButton.disabled = state.selfieBlob === null || state.submitting;
        clearError();
    });

    document.addEventListener('attendance:selfie-cleared', () => {
        state.selfieBlob = null;
        submitButton.disabled = true;
        submitLabel.textContent = state.defaultLabel;
    });

    submitButton.addEventListener('click', submitAttendance);
})();
