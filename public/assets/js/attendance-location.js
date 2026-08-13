'use strict';

(() => {
    const container = document.getElementById('attendanceLocationCheck');

    if (!container) {
        return;
    }

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

    if (!button || !buttonLabel || !token || !statusBox || !statusText || !message) {
        return;
    }

    const formatMeters = (value, prefix = '') => {
        const number = Number(value);

        if (!Number.isFinite(number)) {
            return '--';
        }

        return `${prefix}${Math.round(number)} m`;
    };

    const setState = (tone, title, detail) => {
        statusBox.className = `location-status-box is-${tone}`;
        statusText.textContent = title;
        message.textContent = detail;

        if (stateIcon) {
            stateIcon.className = `location-state-icon is-${tone}`;
            const icons = {
                idle: 'bi-geo-alt',
                loading: 'bi-arrow-repeat',
                success: 'bi-geo-alt-fill',
                warning: 'bi-exclamation-triangle',
                danger: 'bi-geo-alt',
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
            return;
        }

        setState('danger', 'Di luar area absensi', payload.message);
        phaseNote?.classList.add('d-none');
    };

    const submitCoordinates = async (coords) => {
        const body = new FormData();
        body.append('_token', token.value);
        body.append('latitude', String(coords.latitude));
        body.append('longitude', String(coords.longitude));
        body.append('accuracy', String(coords.accuracy));

        try {
            const response = await fetch(container.dataset.endpoint, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
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
            setState('danger', 'Lokasi gagal diperiksa', 'Terjadi gangguan saat menghubungi server. Silakan coba lagi.');
        } finally {
            setLoading(false);
        }
    };

    const handleGeolocationError = (error) => {
        const messages = {
            1: 'Izin lokasi ditolak. Aktifkan izin lokasi untuk melakukan absensi.',
            2: 'Lokasi tidak dapat ditemukan. Pastikan GPS aktif lalu coba lagi.',
            3: 'Pengambilan lokasi terlalu lama. Silakan coba lagi.',
        };

        hideDetails();
        setState('danger', 'Lokasi gagal diperiksa', messages[error.code] || 'Lokasi tidak dapat diperiksa. Silakan coba lagi.');
        setLoading(false);
    };

    button.addEventListener('click', () => {
        hideDetails();

        if (!('geolocation' in navigator)) {
            setState('danger', 'Lokasi gagal diperiksa', 'Browser ini tidak mendukung fitur lokasi.');
            buttonLabel.textContent = 'Periksa Ulang Lokasi';
            return;
        }

        setLoading(true);
        setState('loading', 'Mengambil lokasi...', 'Izinkan browser mengakses lokasi dan tunggu beberapa saat.');

        navigator.geolocation.getCurrentPosition(
            (position) => submitCoordinates(position.coords),
            handleGeolocationError,
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0,
            }
        );
    });
})();
