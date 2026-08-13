'use strict';

(() => {
    const container = document.getElementById('attendanceLocationCheck');

    if (!container) return;

    const button = document.getElementById('checkLocationButton');
    const buttonLabel = button?.querySelector('span');
    const token = document.getElementById('attendanceLocationToken');
    const statusBox = document.getElementById('locationStatusBox');
    const statusText = document.getElementById('locationStatus');
    const message = document.getElementById('locationMessage');
    const stateIcon = document.getElementById('locationStateIcon');
    const result = document.getElementById('locationResult');
    const nearestLocation = document.getElementById('nearestLocation');
    const distance = document.getElementById('locationDistance');
    const radius = document.getElementById('locationRadius');
    const accuracy = document.getElementById('locationAccuracy');
    const phaseNote = document.getElementById('locationPhaseNote');

    if (!button || !buttonLabel || !token || !statusBox || !statusText || !message) return;

    const formatMeters = (value, prefix = '') => {
        const number = Number(value);
        return Number.isFinite(number) ? `${prefix}${Math.round(number)} m` : '--';
    };

    const setState = (tone, title, detail) => {
        statusBox.className = `location-status-box is-${tone}`;
        statusText.textContent = title;
        message.textContent = detail;

        if (stateIcon) {
            stateIcon.className = `location-state-icon is-${tone}`;
            const icons = {
                idle: 'bi-geo-alt', loading: 'bi-arrow-repeat', success: 'bi-geo-alt-fill',
                warning: 'bi-exclamation-triangle', danger: 'bi-geo-alt',
            };
            stateIcon.innerHTML = `<i class="bi ${icons[tone] || icons.danger}"></i>`;
        }
    };

    const setLoading = (loading) => {
        button.disabled = loading;
        buttonLabel.textContent = loading ? 'Memeriksa Lokasi...' : 'Periksa Ulang Lokasi';
        button.querySelector('i')?.classList.toggle('location-spin', loading);
    };

    const hideDetails = () => {
        result?.classList.add('d-none');
        phaseNote?.classList.add('d-none');
    };

    const showAccuracyOnly = (accuracyMeters) => {
        result?.classList.remove('d-none');
        if (nearestLocation) nearestLocation.textContent = '--';
        if (distance) distance.textContent = '--';
        if (radius) radius.textContent = '--';
        if (accuracy) accuracy.textContent = formatMeters(accuracyMeters, '±');
    };

    const showVerification = (payload) => {
        const location = payload.nearest_location;
        result?.classList.remove('d-none');
        if (nearestLocation) nearestLocation.textContent = location.name;
        if (distance) distance.textContent = formatMeters(location.distance_meters);
        if (radius) radius.textContent = formatMeters(location.radius_meters);
        if (accuracy) accuracy.textContent = formatMeters(payload.accuracy_meters, '±');

        if (payload.within_radius) {
            setState('success', 'Lokasi sesuai', payload.message);
            phaseNote?.classList.remove('d-none');
        } else {
            setState('danger', 'Di luar area absensi', payload.message);
            phaseNote?.classList.add('d-none');
        }
    };

    const submitCoordinates = async (coords) => {
        const body = new FormData();
        body.append('_token', token.value);
        body.append('latitude', String(coords.latitude));
        body.append('longitude', String(coords.longitude));
        body.append('accuracy', String(coords.accuracy));

        try {
            const response = await fetch(container.dataset.endpoint, {
                method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json' },
            });
            const payload = await response.json();

            if (!response.ok) {
                hideDetails();
                setState('danger', 'Lokasi gagal diperiksa', payload.message || 'Data lokasi tidak dapat diverifikasi.');
                return;
            }
            if (payload.location_available === false) {
                hideDetails();
                setState('danger', 'Lokasi tidak tersedia', payload.message);
                return;
            }
            if (payload.location_reliable === false) {
                showAccuracyOnly(payload.accuracy_meters);
                setState('warning', 'Lokasi belum akurat', payload.message);
                return;
            }
            showVerification(payload);
        } catch (error) {
            hideDetails();
            setState('danger', 'Lokasi gagal diperiksa', 'Koordinat didapat, tetapi server tidak dapat dihubungi. Muat ulang halaman lalu coba lagi.');
        } finally {
            setLoading(false);
        }
    };

    const finalGeolocationError = (error) => {
        const details = {
            1: 'Izin lokasi ditolak. Buka izin situs/browser, aktifkan Lokasi dan Lokasi Presisi, lalu coba lagi.',
            2: 'Perangkat belum berhasil menentukan posisi. Pastikan Lokasi aktif, aktifkan Lokasi Presisi/Google Location Accuracy, lalu coba di dekat jendela atau area terbuka.',
            3: 'Perangkat terlalu lama menentukan posisi. Pastikan Lokasi aktif lalu coba lagi di area dengan penerimaan GPS yang lebih baik.',
        };
        hideDetails();
        setState('danger', 'Lokasi gagal diperiksa', `${details[error.code] || 'Lokasi tidak dapat diperiksa.'} (kode ${error.code || '?'})`);
        setLoading(false);
    };

    const getLocation = () => {
        // Attempt 1: fresh, high-accuracy GPS fix. Mobile devices can need longer than 15 seconds indoors.
        navigator.geolocation.getCurrentPosition(
            (position) => submitCoordinates(position.coords),
            (firstError) => {
                if (firstError.code === 1) {
                    finalGeolocationError(firstError);
                    return;
                }

                // Attempt 2: allow Android/browser network-assisted or recently cached location.
                setState('loading', 'Mencoba metode lokasi alternatif...', 'GPS presisi belum mendapat posisi. Mencoba lokasi berbantuan jaringan.');
                navigator.geolocation.getCurrentPosition(
                    (position) => submitCoordinates(position.coords),
                    finalGeolocationError,
                    { enableHighAccuracy: false, timeout: 20000, maximumAge: 60000 }
                );
            },
            { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 }
        );
    };

    button.addEventListener('click', () => {
        hideDetails();

        if (!window.isSecureContext) {
            setState('danger', 'Koneksi tidak aman', 'Akses lokasi membutuhkan HTTPS. Buka aplikasi melalui URL HTTPS ngrok.');
            buttonLabel.textContent = 'Periksa Ulang Lokasi';
            return;
        }
        if (!('geolocation' in navigator)) {
            setState('danger', 'Lokasi gagal diperiksa', 'Browser ini tidak mendukung fitur lokasi.');
            buttonLabel.textContent = 'Periksa Ulang Lokasi';
            return;
        }

        setLoading(true);
        setState('loading', 'Mengambil lokasi presisi...', 'Izinkan browser mengakses lokasi. Proses dapat membutuhkan beberapa detik.');
        getLocation();
    });
})();
