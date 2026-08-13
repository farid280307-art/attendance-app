# Attendance App

Aplikasi absensi karyawan berbasis PHP Native, MySQL/MariaDB, Bootstrap 5, dan Vanilla JavaScript.

## Catatan Geolocation

- `localhost` dapat digunakan sebagai secure context untuk pengembangan dan pengujian geolocation.
- Deployment production wajib menggunakan HTTPS agar browser mengizinkan akses geolocation.
- Verifikasi saat ini menggunakan validasi input, lokasi aktif, akurasi GPS, dan perhitungan Haversine di server. Browser geolocation tetap dapat dimanipulasi pada perangkat tertentu dan tidak menjamin pencegahan Fake GPS sepenuhnya.
