# Attendance App

Aplikasi absensi karyawan berbasis PHP Native, MySQL/MariaDB, Bootstrap 5, dan Vanilla JavaScript.

## Catatan Geolocation

- `localhost` dapat digunakan sebagai secure context untuk pengembangan dan pengujian geolocation.
- Deployment production wajib menggunakan HTTPS agar browser mengizinkan akses geolocation.
- Verifikasi saat ini menggunakan validasi input, lokasi aktif, akurasi GPS, dan perhitungan Haversine di server. Browser geolocation tetap dapat dimanipulasi pada perangkat tertentu dan tidak menjamin pencegahan Fake GPS sepenuhnya.

## Catatan Kamera dan Selfie

- Akses kamera memerlukan secure context; gunakan `localhost` saat development dan HTTPS pada production.
- Status lokasi di JavaScript hanya mengatur alur UX. Saat submission attendance diimplementasikan, backend wajib memvalidasi ulang koordinat, akurasi, lokasi aktif, dan radius.
- Selfie Phase 7 diproses sebagai JPEG Blob di memory browser, bukan Base64, dan belum dikirim atau disimpan ke filesystem/database.
- Modul kamera menerbitkan event `attendance:selfie-ready` berisi Blob sementara sebagai kontrak integrasi Phase 8. Submission berikutnya harus menggunakan `FormData` bersama data lokasi yang fresh.
