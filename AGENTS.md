# AGENTS.md

## Gambaran Project

Project ini adalah aplikasi absensi karyawan berbasis web yang paling sering digunakan melalui smartphone. Seluruh perubahan harus menjaga keamanan, kemudahan pemeliharaan, dan pengalaman pengguna yang responsive serta mobile-first.

## Technology Stack

- PHP Native tanpa framework PHP.
- MySQL atau MariaDB.
- Bootstrap 5.
- Vanilla JavaScript.
- CSS.
- XAMPP dengan Apache.
- PDO untuk seluruh akses database.
- Timezone aplikasi: `Asia/Jakarta`.

Jangan menggunakan Laravel, CodeIgniter, Symfony, atau framework PHP lain. Jangan menambahkan dependency baru kecuali benar-benar diperlukan dan alasannya jelas.

## Arsitektur Wajib

Pertahankan struktur MVC-ish berikut:

```text
app/
  Controllers/
  Models/
  Services/
  Validators/
  Middleware/
  Core/
  Support/
  Views/

config/
database/
  migrations/
  seeders/
public/
  assets/
  uploads/
routes/
```

Jangan mengubah struktur folder tanpa alasan kuat. Jika perubahan struktur memang diperlukan, jelaskan alasan dan dampaknya terlebih dahulu.

### Pembagian Tanggung Jawab

- **Controller** menangani alur request dan response. Jangan menaruh business logic kompleks di Controller.
- **Service** menangani business logic kompleks dan koordinasi proses lintas Model.
- **Model** menangani akses dan pemetaan data database.
- **Validator** menangani validasi input di backend.
- **Middleware** menangani concern lintas request seperti autentikasi, otorisasi, dan proteksi request.
- **Core** berisi fondasi aplikasi seperti routing, koneksi database, request, response, atau bootstrap internal.
- **Support** berisi helper atau utilitas umum yang tidak menjadi business logic utama.
- **View** hanya bertanggung jawab pada presentasi. Jangan menaruh query SQL atau business logic kompleks di View.
- **Routes** mendefinisikan pemetaan route ke handler yang sesuai.

## Aturan PHP dan Database

1. Gunakan `declare(strict_types=1);` pada file PHP class jika sesuai.
2. Semua akses database wajib menggunakan PDO.
3. Semua nilai dinamis dalam query wajib menggunakan prepared statements dan parameter binding. Jangan menyusun SQL dengan konkatenasi input pengguna.
4. Query database harus ditempatkan di Model atau lapisan akses data yang memang ditujukan untuk itu, bukan di View.
5. Semua perubahan skema atau data database yang terstruktur harus dibuat melalui migration baru.
6. Jangan mengubah migration lama yang sudah pernah digunakan. Buat migration lanjutan untuk koreksi atau perubahan berikutnya.
7. Gunakan seeder untuk data awal atau data pengembangan yang memang perlu dapat dibuat ulang.
8. Gunakan transaction untuk operasi database majemuk yang harus berhasil atau gagal sebagai satu kesatuan.

## Keamanan

1. Password wajib dibuat dengan `password_hash()` dan diverifikasi dengan `password_verify()`. Jangan menyimpan password plaintext atau membuat algoritma hashing sendiri.
2. Escape seluruh data pengguna saat ditampilkan dalam HTML untuk mencegah XSS. Gunakan escaping yang sesuai konteks, misalnya `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` untuk konten dan atribut HTML.
3. Semua form atau request yang mengubah state, terutama request POST, wajib menggunakan proteksi CSRF dan validasi token di backend.
4. Validasi JavaScript hanya untuk membantu UX. Semua validasi penting wajib dijalankan ulang di backend.
5. Terapkan autentikasi dan otorisasi berdasarkan role di backend. Menyembunyikan elemen UI bukan pengganti pemeriksaan izin.
6. Validasi dan normalisasi file upload: tipe MIME, ekstensi, ukuran, nama file acak, serta lokasi penyimpanan. Jangan mempercayai nama atau tipe file dari client.
7. Hindari membocorkan stack trace, credential, query, path internal, atau detail sensitif lain kepada pengguna akhir.
8. Jika requirement bertentangan dengan keamanan atau struktur aplikasi, prioritaskan solusi yang aman dan jelaskan konflik serta keputusan yang diambil.

## Waktu dan Data Absensi

- Gunakan timezone `Asia/Jakarta` secara konsisten pada konfigurasi PHP, operasi tanggal/waktu, penyimpanan, perhitungan jadwal, keterlambatan, dan tampilan.
- Hindari ketergantungan pada timezone default server yang tidak dinyatakan secara eksplisit.
- Latitude, longitude, accuracy, dan distance harus diperlakukan sebagai data yang perlu divalidasi di backend sebelum dipakai untuk keputusan absensi.
- Geolocation dan validasi radius di sisi browser hanya membantu pengambilan data; keputusan final tetap dilakukan di backend.

## Selfie dan Lampiran

- Jangan menyimpan gambar selfie atau lampiran sebagai base64 atau blob encoded di database.
- Simpan file fisik pada storage upload, dengan lokasi sumber yang ditargetkan sebagai `storage/uploads`, dan simpan hanya path atau metadata yang diperlukan di database.
- Struktur `public/uploads/` tetap harus dipertahankan. Gunakan sebagai area penyajian publik hanya jika desain akses file memang memerlukannya; jangan memindahkan atau mengekspos file sensitif tanpa kontrol akses yang tepat.
- Jika `storage/uploads` belum tersedia, jangan membuat atau mengubah alur upload sampai ada tugas implementasi yang membutuhkannya.

## UI dan Frontend

- UI wajib responsive dan mobile-first karena proses absensi paling sering dilakukan melalui smartphone.
- Gunakan Bootstrap 5, Vanilla JavaScript, dan CSS yang sudah menjadi stack project.
- Jangan memperkenalkan framework frontend tanpa kebutuhan dan persetujuan yang jelas.
- Pertahankan aksesibilitas dasar: label form, fokus keyboard, pesan error yang jelas, dan kontrol yang dapat digunakan pada layar kecil.
- Jangan mengandalkan JavaScript sebagai satu-satunya mekanisme keamanan atau validasi.

## Cara Melakukan Perubahan

1. Inspeksi file, pola, dan alur terkait sebelum melakukan perubahan besar.
2. Pertahankan kode dan fitur yang sudah bekerja. Jangan menghapus atau menulis ulang fitur yang tidak terkait hanya untuk menyelesaikan tugas baru.
3. Buat perubahan sekecil dan sefokus mungkin sesuai scope permintaan.
4. Ikuti konvensi project yang sudah ada selama tidak bertentangan dengan aturan dalam file ini.
5. Jangan melakukan refactor besar, perubahan struktur, atau penambahan dependency sebagai efek samping tanpa alasan yang jelas.
6. Setelah mengedit file PHP, jalankan syntax check dengan `php -l` jika PHP CLI tersedia.
7. Jalankan pengujian atau pemeriksaan relevan lain yang tersedia dan laporkan hasilnya.
8. Jangan mengklaim perubahan selesai jika pemeriksaan penting gagal. Jelaskan kegagalan dan dampaknya.

## Scope Fitur Aplikasi

Fitur utama yang direncanakan meliputi:

- Login Admin dan Karyawan.
- Dashboard berdasarkan role.
- Absensi masuk dan pulang.
- Selfie langsung dari kamera browser.
- Geolocation browser dan validasi radius lokasi kantor.
- Penyimpanan latitude, longitude, accuracy, dan distance.
- Pengaturan lokasi kantor dan jadwal kerja.
- Perhitungan keterlambatan.
- Pengajuan Cuti, Sakit, dan Izin beserta lampiran.
- Approval atau rejection oleh Admin.
- Rekap absensi bulanan.
- Manajemen karyawan.
- Activity log.

Daftar ini adalah konteks arah project, bukan izin untuk mengimplementasikan seluruh fitur sekaligus. Kerjakan hanya fitur atau perubahan yang diminta secara eksplisit pada task aktif.

## Definition of Done

Sebuah perubahan dianggap selesai jika:

- Scope permintaan terpenuhi tanpa perubahan di luar kebutuhan.
- Pembagian tanggung jawab antar-layer tetap benar.
- Akses database aman dengan PDO dan prepared statements.
- Validasi backend, escaping output, CSRF, autentikasi, dan otorisasi diterapkan bila relevan.
- Perubahan database memiliki migration baru bila relevan.
- UI tetap responsive dan mobile-first bila ada perubahan tampilan.
- Syntax check PHP dan pemeriksaan relevan lainnya telah dijalankan bila tersedia.
- Tidak ada fitur yang sudah bekerja ikut rusak atau dihapus.
