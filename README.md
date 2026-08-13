# Attendance App

Attendance App v1.0.0 adalah aplikasi absensi karyawan mobile-first berbasis PHP Native, MySQL/MariaDB, Bootstrap 5, Vanilla JavaScript, dan PDO. Aplikasi menggunakan timezone `Asia/Jakarta`.

## Features

- Login dan dashboard berbasis role Admin/Karyawan.
- Absensi Masuk dan Absensi Pulang dengan GPS, validasi radius server, serta selfie kamera browser.
- Pengajuan Cuti, Sakit, dan Izin dengan lampiran privat serta approval/rejection Admin.
- Manajemen karyawan, lokasi kerja, jadwal dan hari kerja, hari libur, serta kalender kerja.
- Rekap bulanan dengan status Hadir, Terlambat, Alpha, Cuti, Sakit, Izin, Libur, dan Hari Libur.
- Daftar dan detail bukti absensi Admin dengan protected selfie viewer.
- Activity log untuk tindakan operasional penting.

## Requirements

- PHP 8.1 atau lebih baru dengan extension `pdo_mysql`, `fileinfo`, dan `mbstring`.
- MySQL 8+ atau MariaDB yang mendukung constraint dan InnoDB.
- Apache dengan `mod_rewrite` dan `AllowOverride All` untuk directory project.
- XAMPP dapat digunakan untuk development lokal.
- PHP CLI tersedia untuk migration, seeder, lint, dan smoke test.
- Camera dan Geolocation membutuhkan browser modern serta secure context.

Untuk upload, rekomendasi minimal PHP adalah `upload_max_filesize = 5M` dan `post_max_size = 8M`. Batas aplikasi adalah 3 MB untuk selfie JPEG dan 5 MB untuk lampiran pengajuan.

## Local Setup

1. Clone atau salin repository ke `C:\xampp\htdocs\attendance-app`.
2. Aktifkan Apache dan MySQL dari XAMPP Control Panel.
3. Buat database `attendance_app` dengan charset `utf8mb4`.
4. Jalankan migration dan seeder dari root project:

```powershell
php database/migrate.php
php database/seed.php
```

5. Buka `http://localhost/attendance-app`.

Tidak ada dependency Composer atau build frontend yang perlu diinstal.

## Database Setup

Contoh pembuatan database development:

```sql
CREATE DATABASE attendance_app
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Default development menggunakan `127.0.0.1:3306`, database `attendance_app`, user `root`, dan password kosong. Gunakan credential terpisah dengan hak minimum pada staging/production.

## Migration

Jalankan:

```powershell
php database/migrate.php
```

Runner membaca `database/migrations/*.sql` secara ascending dan mencatat file yang sudah sukses pada tabel `migrations`. Migration yang sudah tercatat akan berstatus `[SKIP]`. Jangan mengubah migration `001`–`010` yang telah digunakan dan jangan menjalankan reset/drop pada data operasional.

## Seeder / Initial Admin

Development seeder bersifat idempotent:

```powershell
php database/seed.php
```

Credential bootstrap development:

- Admin: username `admin`, password `admin123`.
- Karyawan demo: username `employee`, password `employee123`.

Credential tersebut hanya untuk setup lokal. Ganti password Admin sebelum staging/production. Pada `APP_ENV=production`, seeder karyawan demo dinonaktifkan dan pembuatan Admin baru wajib menggunakan `ADMIN_SEED_PASSWORD` minimal 12 karakter. Seeder tidak mengubah akun yang username-nya sudah ada.

## Configuration / Environment Variables

Aplikasi membaca environment variable langsung melalui `getenv()`; tidak ada loader file `.env`. Nilai untuk Apache harus dikonfigurasi pada environment service, VirtualHost, atau `SetEnv` yang terlindungi. Nilai CLI harus tersedia pada shell yang menjalankan command.

| Variable | Default development | Keterangan |
| --- | --- | --- |
| `APP_ENV` | `development` | Hanya `development` atau `production`. |
| `APP_DEBUG` | `true` pada development | Set `false` pada production. |
| `APP_URL` | `http://localhost/attendance-app` | URL publik tanpa trailing slash, query, fragment, atau credential. |
| `DB_HOST` | `127.0.0.1` | Host MySQL/MariaDB. |
| `DB_PORT` | `3306` | Port database. |
| `DB_NAME` | `attendance_app` | Nama database. |
| `DB_USER` | `root` | User database. |
| `DB_PASSWORD` | kosong | Password database. |
| `SESSION_SECURE_COOKIE` | mengikuti scheme `APP_URL` | Wajib `true` pada deployment HTTPS. |
| `MAX_LOCATION_ACCURACY_METERS` | `100` | Batas akurasi GPS server, rentang 1–10.000 meter. |
| `ADMIN_SEED_PASSWORD` | `admin123` hanya development | Wajib dan minimal 12 karakter saat membuat Admin di production. |

`APP_URL` juga menentukan base path Router dan cookie session. Aplikasi sengaja tidak mempercayai `X-Forwarded-Host` atau `X-Forwarded-Proto`; jika memakai reverse proxy, atur URL publik melalui `APP_URL` dan konfigurasi HTTPS/proxy pada server tepercaya.

## Running with XAMPP

- Pastikan folder bernama `attendance-app` jika memakai default `APP_URL`.
- Pastikan `mod_rewrite` aktif dan `AllowOverride All` berlaku untuk `C:\xampp\htdocs`.
- Restart Apache setelah mengubah konfigurasi environment atau PHP.
- Akses aplikasi dari `http://localhost/attendance-app`, bukan dengan menjalankan file PHP View secara langsung.

## Employee Flow

1. Login sebagai Karyawan.
2. Buka menu Absensi.
3. Periksa lokasi, izinkan GPS, lalu buka kamera secara manual.
4. Ambil dan konfirmasi selfie.
5. Kirim Absensi Masuk atau Absensi Pulang. Backend mengambil keputusan dari user session, status absensi saat ini, jadwal, koordinat fresh, lokasi aktif, radius, dan akurasi—bukan dari field keputusan milik browser.
6. Pengajuan dan rekap pribadi tersedia melalui menu Pengajuan dan Rekap Saya.

## Admin Flow

Admin dapat mengelola Karyawan, Lokasi Kerja, Jadwal Kerja, Hari Libur, dan Kalender Kerja; memproses pengajuan; membuka rekap; serta melihat daftar/detail bukti absensi. Selfie dan lampiran hanya disajikan melalui route yang melakukan autentikasi serta otorisasi backend.

## Camera / GPS Requirements

- Production wajib HTTPS. `localhost` adalah exception secure-context untuk development.
- URL LAN HTTP seperti `http://192.168.x.x/...` tidak dijamin mendapat akses Camera/Geolocation.
- Kamera tidak diminta otomatis saat page load dan stream dihentikan saat selesai, reset, atau meninggalkan halaman.
- Selfie dikirim sebagai JPEG Blob, bukan Base64.
- Browser hanya membantu UX. Backend mengambil GPS fresh saat submit, memvalidasi rentang koordinat/akurasi, menghitung Haversine, memilih lokasi aktif terdekat, dan memeriksa radius kembali.
- Sistem tidak dapat menjamin pendeteksian Fake GPS.

## Private Storage

- Selfie disimpan pada `storage/attendance/YYYY/MM/DD/<random>.jpg`.
- Lampiran disimpan pada `storage/leave/YYYY/MM/<random>.<ext>`.
- Database hanya menyimpan relative path; file runtime diabaikan Git.
- `storage/.htaccess` harus tetap berisi `Require all denied` dan storage tidak boleh dipetakan menjadi URL publik.
- Folder `storage` harus writable oleh user PHP/Apache, tetapi tidak writable secara luas dan tidak dapat dibaca langsung dari web.
- `public/uploads` dipertahankan sebagai scaffold legacy, tetapi alur v1 tidak menyimpan selfie atau lampiran di sana.

## Verification Commands

Smoke test tidak melakukan write database:

```powershell
php scripts/smoke-test.php
```

Lint seluruh PHP di PowerShell:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

Jika Node.js tersedia, periksa JavaScript:

```powershell
Get-ChildItem public\assets\js -Filter *.js | ForEach-Object { node --check $_.FullName }
```

## Production Deployment Checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL` ke URL HTTPS sebenarnya.
- Set `SESSION_SECURE_COOKIE=true`; verifikasi cookie `Secure`, `HttpOnly`, dan `SameSite=Lax` dari browser.
- Gunakan credential database production dengan least privilege dan jangan menyimpan secret di repository.
- Jangan memakai credential seeder default; set `ADMIN_SEED_PASSWORD` untuk bootstrap lalu segera gunakan password unik yang dikelola aman.
- Aktifkan HTTPS, HSTS pada web server setelah HTTPS tervalidasi, `mod_rewrite`, dan `AllowOverride All`.
- Set `display_errors=Off` dan `log_errors=On`; lindungi dan rotasi log server.
- Pastikan timezone PHP dan database menghasilkan waktu `Asia/Jakarta`/UTC+7.
- Pastikan `storage` writable oleh PHP tetapi terblokir dari HTTP; uji selfie/lampiran protected dan backup terenkripsi/terbatas.
- Sesuaikan `upload_max_filesize` dan `post_max_size` dengan batas aplikasi.
- Batasi akses Admin, gunakan permission filesystem/database minimum, dan terapkan rate limiting login di reverse proxy/web server bila tersedia.
- Siapkan backup database dan storage, prosedur restore, retention data selfie/koordinat, monitoring, serta pengujian di perangkat mobile aktual.
- Jalankan migration, smoke test, lint PHP/JS, dan matriks authorization pada environment staging.

## Security Notes

- Semua query dinamis menggunakan PDO prepared statements; identifier dinamis seperti tipe selfie dipilih dari whitelist server-side.
- Seluruh mutation memakai POST dan proteksi CSRF backend.
- Session ID diregenerasi setelah login/logout dan user aktif beserta role diverifikasi ulang dari database pada setiap request.
- Response dinamis mengirim header `nosniff`, `SAMEORIGIN`, referrer policy, serta Permissions Policy yang tetap mengizinkan Camera/Geolocation dari origin sendiri.
- Tidak ada CSP global agresif karena aplikasi masih memakai markup inline dan CDN Bootstrap. Protected selfie/lampiran mempunyai policy response khusus. CSP nonce/hash dapat menjadi hardening lanjutan setelah asset CDN/inline dirapikan dan diuji.
- Selfie dan koordinat adalah data sensitif operasional. Batasi akses Admin, tetapkan retention policy, lindungi backup, dan selalu gunakan HTTPS.

## Known Limitations

- Browser Geolocation, accuracy, Haversine, dan radius validation tidak dapat menjamin deteksi Fake GPS.
- Tidak ada face recognition atau liveness detection; Admin hanya dapat meninjau bukti selfie.
- Checkout tidak menyimpan `work_location_id` terpisah; detail hanya menampilkan jarak, akurasi, dan koordinat checkout.
- Backfill kalender historis yang belum memiliki snapshot memakai jadwal yang sedang terpasang; Admin harus memverifikasi kesesuaiannya.
- Belum ada sinkronisasi otomatis hari libur nasional.
- Belum ada workflow koreksi attendance manual.
- Belum ada export PDF/Excel pada v1.
- Camera/GPS production membutuhkan HTTPS dan tetap bergantung pada dukungan/izin browser serta perangkat.
