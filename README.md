# SIMLAP-Riset

Aplikasi pelaporan kemajuan penelitian berbasis PHP 8.2 tanpa framework. Pengguna mengisi laporan melalui wizard 5 langkah; admin meninjau dan menyetujui laporan. Validasi dilakukan di server, dilengkapi proteksi CSRF, token alur bertanda tangan, rate limit, idempotency, dan dual approval.

## Fitur

- Wizard 5 langkah: ketua, anggota, rincian penelitian, luaran, tinjau & kirim.
- Validasi server-side lengkap: format NIP/NPM, email, telepon, rentang biaya, aturan lintas-field, dan batas jumlah baris.
- Penguncian alur: langkah tidak bisa dilompati; mengubah langkah sebelumnya memaksa tinjau ulang.
- Nomor registrasi otomatis: `LRP-YYYY-NNNNNN`.
- Halaman sukses yang hanya bisa diakses oleh sesi pengirim.
- Panel admin: login, daftar laporan, pencarian/filter status, detail, setujui/tolak.
- Dual approval untuk biaya >= Rp100.000.000 (dua admin berbeda).
- Audit log dan log aplikasi di `storage/logs`.
- UI responsif tanpa aset eksternal (CSP ramah, tanpa Google Fonts/CDN).

## Kebutuhan

- PHP 8.2+ dengan ekstensi `pdo`, `mbstring`, `intl`.
- SQLite (untuk demo) atau MySQL/MariaDB (produksi).
- Composer tidak wajib untuk menjalankan aplikasi.

Contoh path PHP di XAMPP Windows:

```powershell
C:\xampp\php\php.exe -v
```

## Instalasi

### Opsi A - SQLite (demo cepat)

`.env` bawaan proyek sudah menggunakan SQLite. Jalankan dari root proyek:

```powershell
C:\xampp\php\php.exe database/install.php
```

Installer akan membuat `storage/database.sqlite`, menerapkan skema, dan membuat akun admin. Installer hanya bisa dijalankan lewat CLI dan aman dijalankan ulang; data laporan yang sudah ada tidak dihapus.

### Opsi B - MySQL/MariaDB (produksi)

Ubah `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=0
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simlap_riset
DB_USERNAME=root
DB_PASSWORD=
```

Pastikan server MySQL/MariaDB berjalan, lalu:

```powershell
C:\xampp\php\php.exe database/install.php
```

User database perlu izin membuat database, atau database `simlap_riset` harus sudah dibuat terlebih dahulu.

## Menjalankan Aplikasi

### Server pengembangan PHP

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public public/router.php
```

Buka `http://127.0.0.1:8000`.

`public/router.php` hanya untuk server pengembangan. Untuk Apache, arahkan DocumentRoot ke folder `public`; `public/.htaccess` sudah menangani front controller.

### Akun admin default

| Username | Password |
|---|---|
| `admin` | `Admin#12345` |
| `admin2` | `Admin#67890` |

Ganti sebelum produksi dengan mengatur `ADMIN_PASSWORD` dan `ADMIN2_PASSWORD` di environment, lalu jalankan ulang installer (password admin akan diperbarui).

Halaman admin: `http://127.0.0.1:8000/admin/login`

## Alur Singkat

1. Isi langkah 1 (ketua tim), tunggu minimal 5 detik per langkah.
2. Isi langkah 2 (anggota), langkah 3 (rincian), langkah 4 (luaran).
3. Tinjau ringkasan di langkah 5, centang pernyataan, lalu kirim.
4. Simpan nomor registrasi yang tampil.
5. Admin membuka `/admin/laporan`, memeriksa detail, lalu menyetujui/menolak.
6. Jika biaya >= Rp100.000.000 dan `ENABLE_DUAL_APPROVAL=1`, laporan berstatus `DITINJAU` sampai disetujui dua admin berbeda.

## Keamanan yang Diterapkan

- CSRF token pada semua form POST.
- Token alur HMAC terikat ke session, langkah, waktu, dan nonce.
- Minimal 5 detik per langkah dan minimal 60 detik total sebelum submit.
- Honeypot `_hp_website` dan penolakan parameter POST tak dikenal.
- Rate limit submit per IP/NIP dan batas global harian.
- Penundaan progresif dan penguncian sesi setelah gagal validasi berulang.
- Idempotency key untuk mencegah submit ganda.
- Transaksi database atomik saat menyimpan laporan.
- Hash password `password_hash`/`password_verify`; query PDO prepared.
- Header keamanan: CSP, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, dan `Permissions-Policy`.
- Session `HttpOnly`, `SameSite=Lax`, regenerasi ID setelah login/submit.

## Struktur Proyek

```
config/          Konfigurasi aplikasi, database, validasi, limit
database/        Skema MySQL/SQLite dan installer
public/          Front controller, router dev, aset CSS/JS, favicon
src/Core/        Database, session, request, response, router, view
src/Validation/  Validator per langkah dan aturan lintas-field
src/Services/    Alur wizard, submit, approval, rate limit, counter
src/Repositories Data access untuk laporan, admin, audit
src/Controllers/ Controller web dan admin
templates/       Layout, wizard, admin, halaman error
storage/         Database SQLite dan log aplikasi
```

## Verifikasi

Lint semua file PHP:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object {
    & C:\xampp\php\php.exe -l $_.FullName
}
```

Status verifikasi terakhir:

- PHP lint: 65 file, 0 error.
- Smoke test end-to-end: submit wizard, halaman sukses, login admin, approval pertama (`DITINJAU`), approval kedua (`DISETUJUI`) berhasil.
- Pemeriksaan negatif: halaman sukses tanpa sesi `403`, route tidak dikenal `404`, POST tanpa CSRF `419`.

## Catatan Produksi

- Gunakan HTTPS dan set `APP_ENV=production`, `APP_DEBUG=0`.
- Ganti `APP_KEY` dan `APP_HMAC_KEY` dengan nilai acak yang kuat.
- Ganti password admin default.
- Pastikan `storage/` tidak dapat diakses langsung dari web; hanya `public/` yang menjadi DocumentRoot.
- Jalankan installer hanya dari CLI, bukan melalui browser.
- Uji jalur MySQL/MariaDB di lingkungan target sebelum deploy; verifikasi saat ini dilakukan dengan SQLite.
