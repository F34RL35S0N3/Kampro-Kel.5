# PRD — Aplikasi Web "Laporan Perkembangan Penelitian"

| Atribut | Nilai |
|---|---|
| Nama produk | **SIMLAP-Riset** (Sistem Pelaporan Perkembangan Penelitian) — nama kerja, dapat diganti |
| Versi dokumen | 1.0 (Draft untuk implementasi) |
| Tanggal | 9 November 2026 |
| Bahasa pemrograman | **PHP 8.2+** (tanpa framework; PDO + MySQL/MariaDB) |
| Standar keamanan | **OWASP ASVS 5.0.0 — Bab V2: Validation and Business Logic** (target Level 2, plus item Level 3 yang terpilih) |
| Sumber form | `Kelompok_lain.docx` — *Contoh Format Laporan Perkembangan Penelitian* |
| Sumber desain | Gambar terlampir + Dribbble "Contact Us Form" (https://dribbble.com/shots/14267156-Contact-Us-Form) |

---

## 1. Ringkasan Eksekutif

SIMLAP-Riset adalah aplikasi web berbasis PHP yang mendigitalkan dokumen **"Format Laporan Perkembangan Penelitian"** menjadi form online bertahap (wizard). Peneliti (ketua tim) mengisi data tim pelaksana, anggota tim, rincian penelitian, dan target luaran. Data divalidasi ketat di sisi server sesuai **ASVS V2** (aturan validasi terdokumentasi, validasi di trusted service layer, konsistensi antar-data, urutan alur bisnis, batas bisnis, transaksi atomik, penguncian, dan anti-otomasi), lalu disimpan dan dapat ditinjau oleh admin.

Tampilan mengikuti referensi: **kartu terbelah dua** — panel kiri ungu bergradasi dengan headline putih dan kartu berwarna, panel kanan putih/kaca berisi form dengan input membulat, tombol *Submit* biru, ikon gelembung chat 3D merah muda, dan latar blur pastel.

## 2. Tujuan, Non-Tujuan, dan Metrik

### 2.1 Tujuan
1. Menyediakan form online yang **setara 100% dengan field pada dokumen docx** (tidak ada field wajib yang hilang).
2. Memenuhi seluruh requirement **ASVS 5.0.0 V2.1.x – V2.4.x** pada Level 2, ditambah V2.3.5 dan V2.4.2 (Level 3) sebagai fitur yang dapat diaktifkan lewat konfigurasi.
3. Menghasilkan UI yang menyerupai referensi desain, responsif dan aksesibel.
4. Data tersimpan konsisten (atomik), bebas duplikasi, dan bebas *garbage data*.

### 2.2 Non-Tujuan (out of scope versi 1.0)
- Bab ASVS lain (V1, V3–V17) **tidak** menjadi target verifikasi formal; namun kontrol pendukung minimum (CSRF, output encoding, parameterized query, security header, hashing password admin) tetap diwajibkan karena V2 sendiri menyatakan validasi tidak menggantikan encoding/parameterisasi.
- Integrasi SSO kampus, pengiriman email massal, unggah dokumen lampiran.
- Aplikasi mobile native.

### 2.3 Metrik Keberhasilan
| Metrik | Target |
|---|---|
| Kelengkapan field vs docx | 100% |
| Pengujian aturan validasi V2 (otomatis) | 100% lulus |
| Submission invalid yang lolos ke DB | 0 |
| Submission duplikat akibat double-click/replay | 0 |
| Waktu pengisian rata-rata | ≤ 10 menit |
| Lighthouse Accessibility | ≥ 90 |

## 3. Pengguna dan Peran

| Peran | Deskripsi | Hak akses |
|---|---|---|
| **Pelapor (Ketua Tim)** | Dosen/peneliti yang mengisi laporan. Tanpa akun pada v1.0 (anonim bersesi). | Mengisi wizard, mengirim, melihat halaman sukses/ringkasan miliknya pada sesi yang sama |
| **Admin Reviewer** | Petugas LPPM/penjamin mutu. | Login, melihat daftar & detail laporan, mengubah status |
| **Admin Approver (opsional, L3)** | Pimpinan/penyetuju kedua. | Memberi persetujuan kedua untuk laporan biaya tinggi |

## 4. Ruang Lingkup Fungsional

### 4.1 Pemetaan Field Dokumen → Form

Seluruh field berikut berasal dari `Kelompok_lain.docx`. Catatan: label asli "Telp/HP/" pada docx terpotong dan ditulis sebagai **Telp/HP**; "Kelompok Keilmuan" pada docx berada di antara Fakultas/Sekolah dan Telp/HP sehingga diperlakukan sebagai bagian data **Ketua Tim**.

| Bagian docx | Field | Kunci field | Tipe | Wajib |
|---|---|---|---|---|
| **Tim Pelaksana → Ketua Tim** | Nama Lengkap | `ketua_nama` | teks | Ya |
| | Jabatan | `ketua_jabatan` | teks | Ya |
| | NIP | `ketua_nip` | numerik 18 digit | Ya |
| | Fakultas/Sekolah | `ketua_fakultas` | teks | Ya |
| | Kelompok Keilmuan | `ketua_kelompok_keilmuan` | teks | Ya |
| | Telp/HP | `ketua_telp` | telepon Indonesia | Ya |
| | E-mail | `ketua_email` | email | Ya |
| **Anggota Tim Riset/Inovasi** (tabel dinamis) | No | otomatis (urutan baris) | — | — |
| | Nama dan Gelar Akademik | `anggota[i][nama]` | teks | Ya |
| | NIP / NPM | `anggota[i][nip_npm]` | numerik | Ya |
| | Bidang Ilmu / Kelas | `anggota[i][bidang_kelas]` | teks | Ya |
| **Rincian Penelitian** | Lama Penelitian (dalam minggu) | `lama_minggu` | integer | Ya |
| | Biaya Penelitian (dalam rupiah) | `biaya_rupiah` | integer | Ya |
| | Jenis Penelitian (dasar/terapan) | `jenis_penelitian` | enum: `dasar`, `terapan` | Ya |
| | CPL Program Studi Terkait | `cpl_prodi` | teks panjang | Ya |
| | Tema RIP Terkait | `tema_rip` | teks | Ya |
| | Mitra Penelitian | `mitra` | teks | Kondisional (lihat §6.3) |
| | Potensi Adopsi Luaran | `potensi_adopsi` | teks panjang | Ya |
| **Target Output (Luaran)** (tabel dinamis) | No | otomatis | — | — |
| | Nama/Jenis output | `luaran[i][jenis]` + `luaran[i][nama]` | enum + teks | Ya |
| | Jumlah | `luaran[i][jumlah]` | integer | Ya |

### 4.2 Alur Wizard (5 Langkah)

Alur dipaksa berurutan di server (ASVS 2.3.1).

| Langkah | Nama | Isi |
|---|---|---|
| 1 | Ketua Tim | 7 field ketua |
| 2 | Anggota Tim | Tabel anggota (min 1, maks 10 baris) |
| 3 | Rincian Penelitian | Lama, biaya, jenis, CPL, tema RIP, mitra, potensi adopsi |
| 4 | Target Luaran | Tabel luaran (min 1, maks 10 baris) |
| 5 | Tinjau & Kirim | Ringkasan read-only, centang pernyataan kebenaran data, tombol Kirim |

### 4.3 User Stories dan Kriteria Penerimaan

| ID | Cerita | Kriteria penerimaan |
|---|---|---|
| US-01 | Sebagai pelapor, saya mengisi data ketua tim. | Semua field tervalidasi; error tampil inline per field; data tersimpan di sesi server hanya jika valid. |
| US-02 | Sebagai pelapor, saya menambah/menghapus baris anggota. | Tombol "+ Tambah Anggota" dan hapus baris; nomor otomatis; batas 1–10; NIP/NPM tidak boleh duplikat. |
| US-03 | Sebagai pelapor, saya mengisi rincian penelitian. | Aturan rentang, enum, dan konsistensi antar-field (§6.3) berlaku. |
| US-04 | Sebagai pelapor, saya mengisi target luaran. | Jenis dari daftar yang diizinkan; jumlah 1–99; batas 1–10 baris. |
| US-05 | Sebagai pelapor, saya meninjau seluruh isian sebelum kirim. | Ringkasan menampilkan semua data ter-encode; tombol ubah kembali ke langkah terkait. |
| US-06 | Sebagai pelapor, saya mengirim laporan. | Seluruh data **divalidasi ulang total di server**; transaksi atomik; nomor registrasi unik diberikan; kirim ganda tidak membuat duplikat. |
| US-07 | Sebagai pelapor, saya tidak dapat melompati langkah. | Akses langsung ke `/langkah/3` tanpa menuntaskan langkah 1–2 diarahkan ke langkah pertama yang belum selesai. |
| US-08 | Sebagai admin, saya melihat daftar dan detail laporan. | Tabel dengan pencarian & paginasi; detail lengkap; semua output ter-encode. |
| US-09 | Sebagai admin approver, saya menyetujui laporan biaya tinggi. | Status "Disetujui" hanya setelah 2 admin **berbeda** menyetujui (L3, V2.3.5). |
| US-10 (P2) | Sebagai pelapor, saya mengunduh laporan sebagai PDF berformat seperti docx. | PDF memuat seluruh bagian dokumen asli. |

Prioritas: US-01 s.d. US-08 = **P0**; US-09 = **P1**; US-10 = **P2**.

## 5. Desain Antarmuka (UI/UX)

### 5.1 Konsep Visual (mengikuti gambar referensi)

Kartu modal tunggal di tengah layar, rasio sekitar 16:9 pada desktop, terbagi dua panel:

**Panel kiri (± 35% lebar) — "Info & Progres"**
- Latar ungu bergradasi (ungu tua ke ungu terang) dengan **pola garis kontur halus** (SVG *wave lines*) di bagian bawah.
- Eyebrow berwarna **teal**: *"Laporan Perkembangan Penelitian"* (menggantikan "Let's Connect with us").
- Headline putih tebal: **"Lengkapi data tim dan rencana penelitian Anda."**
- **Tiga kartu berwarna ala kartu tim** pada referensi (merah muda tegas, ungu terang, merah muda pastel), dipakai sebagai **indikator langkah wizard** (nomor lingkaran menggantikan avatar). Langkah aktif memakai kartu merah muda tegas; langkah selesai berpita ungu terang; langkah belum dikerjakan merah muda pastel. Lima langkah ditampilkan bertumpuk bergeser seperti pada referensi.
- Paragraf pendukung berukuran kecil: ringkasan tujuan dan perkiraan waktu pengisian.
- Baris ikon kecil di bawah (ganti ikon sosial dengan ikon: bantuan, panduan, kontak LPPM).
- **Ikon gelembung chat 3D merah muda** (tiga titik putih) menumpang di batas antara panel kiri dan kanan, di bagian bawah, seperti referensi. Implementasi: SVG/PNG dengan bayangan lembut.

**Panel kanan (± 65% lebar) — "Form"**
- Latar putih keabu-abuan semi-transparan (efek *frosted glass*), sudut kartu kanan membulat.
- Tombol **tutup bulat** (ikon ×) di pojok kanan atas; pada aplikasi ini berfungsi "Batalkan & kembali" dengan dialog konfirmasi.
- Judul navy tebal, contoh: *"Isi data ketua tim sebagai langkah awal.."* (gaya kalimat sapaan seperti "Wish to proceed, let us know a little about you as handshake..").
- Input: putih, sudut membulat ±10px, bayangan sangat halus, placeholder abu-abu, **grid 2 kolom** untuk field pendek (mis. Nama | Jabatan; NIP | Fakultas/Sekolah).
- Textarea besar untuk CPL, Tema RIP, Potensi Adopsi.
- Tabel dinamis (anggota & luaran) tampil sebagai **baris input** dengan gaya input yang sama, tombol hapus ikon bulat kecil, dan tombol "+ Tambah" bergaya *ghost*.
- Tombol utama **"Lanjut"/"Kirim"**: biru royal, lebar ±180px, tinggi 44px, sudut membulat 8px, teks putih. Tombol sekunder "Kembali" bergaya *outline*.
- Di sebelah tombol: blok **"Butuh bantuan?"** dengan ikon WhatsApp dan nomor kontak LPPM (placeholder, dapat diatur di `config`), mengikuti blok "Direct connect with us".

**Latar halaman**: putih dengan **noda gradien blur pastel** (biru, ungu, jingga-peach) di sisi kiri/kanan kartu, memakai `radial-gradient` + `filter: blur(80px)`.

### 5.2 Design Tokens (estimasi dari gambar; dapat dipoles)

```css
:root {
  /* Warna */
  --purple-900: #4A14B0;
  --purple-700: #5B1FCB;     /* panel kiri */
  --purple-500: #7A1FFF;     /* kartu ungu terang */
  --pink-500:  #F2679B;      /* kartu aktif */
  --pink-300:  #FF9DBD;      /* kartu pastel */
  --teal-400:  #1FE0C4;      /* eyebrow */
  --navy-900:  #0E1240;      /* judul panel kanan */
  --blue-600:  #3F51D1;      /* tombol utama */
  --text-600:  #5B6075;
  --surface:   rgba(255,255,255,.72);
  --input-bg:  #FFFFFF;
  --error:     #D92D4B;
  --success:   #14A06B;

  /* Bentuk */
  --radius-card: 6px;
  --radius-input: 10px;
  --radius-btn: 8px;
  --shadow-input: 0 2px 10px rgba(30,30,80,.06);
  --shadow-card: 0 30px 80px rgba(60,40,160,.15);

  /* Tipografi */
  --font: "Inter", system-ui, -apple-system, "Segoe UI", sans-serif;
}
```

Tipografi: **Inter**. Judul panel kanan 28/36 px semibold; label 13 px; input 14 px; headline panel kiri 32/40 px bold.

### 5.3 Perilaku Responsif
| Breakpoint | Perilaku |
|---|---|
| ≥ 1024 px | Dua panel berdampingan; panel kanan *scroll* internal, panel kiri tetap. |
| 640–1023 px | Panel kiri mengecil (hanya eyebrow, headline, stepper horizontal); form di bawahnya. |
| < 640 px | Panel kiri menjadi *header* ringkas dengan stepper; kartu penuh lebar; grid 2 kolom menjadi 1 kolom; tombol penuh lebar. |

### 5.4 Aksesibilitas
- Semua input memiliki `<label>` terhubung (`for`/`id`); placeholder bukan pengganti label.
- Pesan error memakai `aria-describedby` + `role="alert"`; fokus berpindah ke error pertama setelah submit gagal.
- Kontras teks ≥ 4.5:1 (teks teal di atas ungu perlu diperiksa; gunakan `#3DF0D6` bila kurang).
- Navigasi keyboard penuh; indikator fokus terlihat; `prefers-reduced-motion` dihormati.
- Dialog tutup memakai `role="dialog"` dan *focus trap*.

### 5.5 Status UI
Kosong, memuat, error validasi, error sistem (halaman generik tanpa detail teknis), terkunci karena *rate limit* (menampilkan waktu tunggu), sukses (nomor registrasi + ringkasan).

## 6. Dokumentasi Validasi dan Logika Bisnis (ASVS V2.1)

Bagian ini **adalah artefak dokumentasi yang dipersyaratkan ASVS V2.1.1, V2.1.2, dan V2.1.3**. Aturan di sini wajib diimplementasikan sebagai kode di `src/Validation/` dan diuji.

### 6.1 Prinsip Validasi
1. **Positive validation (allow-list)** untuk semua input — pola, rentang, enum (V2.2.1). Karena target L2, **seluruh** input divalidasi, bukan hanya yang memengaruhi keputusan keamanan.
2. **Validasi wajib di server** (trusted service layer). Validasi JavaScript hanya untuk UX dan tidak pernah dipercaya (V2.2.2).
3. Input dinormalisasi sebelum divalidasi: `trim`, normalisasi Unicode NFC, hapus karakter kontrol (kecuali `\n` pada textarea), spasi ganda dipadatkan. Encoding wajib UTF-8 valid (`mb_check_encoding`).
4. **Tipe ketat**: nilai dari `$_POST` selalu string; konversi ke integer hanya setelah lolos regex digit. Parameter tak dikenal ditolak (mass-assignment dicegah dengan skema allow-list kunci).
5. Validasi **tidak menggantikan** encoding output (`htmlspecialchars` dengan `ENT_QUOTES | ENT_SUBSTITUTE`, UTF-8) maupun query berparameter PDO.
6. Pesan error ke pengguna bersifat spesifik pada field (aman), tetapi tidak membocorkan detail internal.

### 6.2 Aturan Validasi per Field (V2.1.1, V2.2.1)

**Catatan asumsi**: panjang NIP/NPM, ambang biaya, dan rentang lama penelitian di bawah adalah nilai awal yang masuk akal dan **dapat diubah di `config/limits.php`**; konfirmasi dengan pemangku kepentingan (lihat §14).

#### A. Ketua Tim
| Field | Aturan (allow-list) | Pesan error |
|---|---|---|
| `ketua_nama` | Wajib; 3–100 karakter; regex Unicode `^[\p{L}\p{M}][\p{L}\p{M}\s.,'’\-]{2,99}$` (huruf, titik, koma untuk gelar, apostrof, tanda hubung) | "Nama lengkap 3–100 karakter, hanya huruf dan tanda baca nama/gelar." |
| `ketua_jabatan` | Wajib; 2–100 karakter; `^[\p{L}\p{M}\p{N}\s.,/&()\-]{2,100}$` | "Jabatan tidak valid." |
| `ketua_nip` | Wajib; tepat **18 digit**; `^\d{18}$`; (opsional: validasi struktur tanggal lahir pada digit 1–8 = `YYYYMMDD` valid) | "NIP harus 18 digit angka." |
| `ketua_fakultas` | Wajib; 2–150 karakter; pola seperti jabatan | "Fakultas/Sekolah tidak valid." |
| `ketua_kelompok_keilmuan` | Wajib; 2–150 karakter; pola seperti jabatan | "Kelompok keilmuan tidak valid." |
| `ketua_telp` | Wajib; dinormalisasi lalu `^\+62[8][1-9][0-9]{7,11}$`; input boleh berawal `08`, `628`, atau `+628` (spasi/tanda hubung dibuang) | "Nomor telepon Indonesia tidak valid." |
| `ketua_email` | Wajib; ≤ 254 karakter; `filter_var(FILTER_VALIDATE_EMAIL)` + bagian lokal ≤ 64; dikonversi ke huruf kecil; tolak karakter kontrol & spasi | "Alamat e-mail tidak valid." |

#### B. Anggota Tim (per baris, 1–10 baris)
| Field | Aturan | Pesan error |
|---|---|---|
| `nama` | Wajib; 3–100; pola nama seperti Ketua (mengizinkan gelar akademik, mis. "Dr. Budi, S.Kom., M.T.") | "Nama dan gelar tidak valid." |
| `nip_npm` | Wajib; **NIP 18 digit** atau **NPM 8–15 digit**; `^\d{18}$` atau `^\d{8,15}$` | "Isi NIP (18 digit) atau NPM (8–15 digit)." |
| `bidang_kelas` | Wajib; 1–100; `^[\p{L}\p{M}\p{N}\s.,/&()\-]{1,100}$` | "Bidang ilmu/kelas tidak valid." |
| Antar-baris | `nip_npm` **unik** dalam daftar dan **tidak sama** dengan `ketua_nip` | "NIP/NPM duplikat." |

#### C. Rincian Penelitian
| Field | Aturan | Pesan error |
|---|---|---|
| `lama_minggu` | Wajib; `^\d{1,3}$`; integer **1–104** | "Lama penelitian 1–104 minggu." |
| `biaya_rupiah` | Wajib; input boleh memakai pemisah titik/spasi ("Rp 1.500.000"), dinormalisasi ke digit saja; `^\d{1,12}$`; integer **1 – 5.000.000.000**; disimpan sebagai `BIGINT UNSIGNED` (tanpa float) | "Biaya harus angka rupiah bulat, 1 s.d. 5 miliar." |
| `jenis_penelitian` | Wajib; **enum** `dasar` \| `terapan` (perbandingan `in_array(..., true)`) | "Pilih jenis penelitian." |
| `cpl_prodi` | Wajib; 5–1000 karakter; teks bebas multi-baris (≤ 20 baris); karakter kontrol ditolak | "CPL 5–1000 karakter." |
| `tema_rip` | Wajib; 3–500 karakter | "Tema RIP 3–500 karakter." |
| `mitra` | 0–300 karakter; boleh "Tidak ada"; kondisional (§6.3) | "Mitra maksimal 300 karakter." |
| `potensi_adopsi` | Wajib; 10–1000 karakter | "Potensi adopsi 10–1000 karakter." |

#### D. Target Luaran (per baris, 1–10 baris)
| Field | Aturan | Pesan error |
|---|---|---|
| `jenis` | Wajib; **enum** daftar tetap: `jurnal_nasional`, `jurnal_internasional`, `prosiding`, `hki`, `buku`, `prototipe_produk`, `laporan_teknis`, `lainnya` | "Pilih jenis luaran." |
| `nama` | Wajib bila `jenis = lainnya` atau diperlukan keterangan; 3–200 karakter | "Nama luaran 3–200 karakter." |
| `jumlah` | Wajib; `^\d{1,2}$`; integer **1–99** | "Jumlah 1–99." |
| Antar-baris | Kombinasi (`jenis`, `nama` ternormalisasi huruf kecil) **tidak boleh duplikat** | "Luaran duplikat." |

#### E. Field Pendukung Keamanan (bukan bagian docx)
| Field | Aturan |
|---|---|
| `_csrf` | Token sesi acak 32 byte, cocok dengan sesi (`hash_equals`) |
| `_flow` | Token alur: HMAC-SHA256(`session_id | step | issued_at`), umur ≤ 2 jam |
| `_hp_website` | **Honeypot**: harus kosong |
| `agree` (langkah 5) | Harus bernilai `1` |
| `idem_key` (langkah 5) | UUIDv4 sekali pakai (idempotensi) |

### 6.3 Konsistensi Kontekstual antar-Data (V2.1.2, V2.2.3)

Aturan lintas-field yang **harus** dicek di server:

| ID | Aturan | Tindakan jika gagal |
|---|---|---|
| CX-01 | `ketua_nip` tidak boleh sama dengan `nip_npm` anggota mana pun. | Tolak di Langkah 2 |
| CX-02 | Jika `jenis_penelitian = terapan` maka **`mitra` wajib diisi** (selain nilai kosong/"-"), dan `potensi_adopsi` minimal 30 karakter. | Tolak di Langkah 3 |
| CX-03 | Jika `biaya_rupiah` ≥ **Rp 100.000.000** maka `mitra` wajib diisi dan jumlah anggota ≥ 2. | Tolak di Langkah 3 (dan tandai butuh persetujuan ganda, §6.5) |
| CX-04 | **Kewajaran biaya per minggu**: `biaya_rupiah / lama_minggu` harus antara Rp 10.000 dan Rp 500.000.000. | Tolak di Langkah 3 |
| CX-05 | **Kewajaran luaran terhadap durasi**: jika `lama_minggu ≤ 8`, total `jumlah` luaran ≤ 5; jika `lama_minggu ≤ 24`, ≤ 20; selain itu ≤ 50. | Tolak di Langkah 4 |
| CX-06 | Jika `jenis_penelitian = dasar` maka minimal satu luaran bertipe `jurnal_nasional`, `jurnal_internasional`, atau `prosiding`. | Tolak di Langkah 4 |
| CX-07 | Domain e-mail ketua tidak boleh berupa domain *disposable* (daftar tolak yang dapat dikonfigurasi). | Tolak di Langkah 1 |
| CX-08 | **Validasi ulang total** seluruh langkah 1–4 pada saat Kirim (data sesi dianggap tidak tepercaya). | Kembalikan ke langkah pertama yang gagal |

> Aturan CX-02 sampai CX-06 adalah asumsi bisnis awal yang masuk akal. Pemilik produk dapat mengubah ambang/aturannya; yang penting, aturan tetap terdokumentasi di bagian ini dan konsisten dengan kode (V2.1.2).

### 6.4 Batas Bisnis Per-Pengguna dan Global (V2.1.3, V2.3.2)

| ID | Batas | Lingkup | Nilai awal | Respons saat terlampaui |
|---|---|---|---|---|
| LIM-01 | Jumlah pengiriman laporan | Per IP | 5 / jam | HTTP 429 + `Retry-After` |
| LIM-02 | Jumlah pengiriman laporan | Per NIP ketua | 3 / 24 jam | Tolak dengan pesan umum |
| LIM-03 | Jumlah pengiriman laporan | Global | 500 / hari | HTTP 503 halaman "kuota harian penuh" |
| LIM-04 | Percobaan validasi gagal | Per sesi | 30 / jam | Sesi dikunci 15 menit |
| LIM-05 | Sesi wizard aktif | Per IP | 10 | Sesi tertua dibuang |
| LIM-06 | Ukuran body request | Per request | 64 KB | HTTP 413 |
| LIM-07 | Baris anggota / luaran | Per laporan | 10 / 10 | Validasi gagal |
| LIM-08 | Panjang total teks | Per laporan | 12.000 karakter | Validasi gagal |
| LIM-09 | Laporan identik (hash SHA-256 payload ternormalisasi) | Global | 1 / 30 hari | Tolak sebagai duplikat |
| LIM-10 | Umur sesi wizard | Per sesi | 2 jam absolut, 30 menit idle | Mulai ulang |

Seluruh nilai disimpan di `config/limits.php` agar dapat disetel tanpa mengubah kode.

### 6.5 Alur Bisnis, Transaksi, Penguncian, dan Persetujuan (V2.3.x)

**Mesin status alur (V2.3.1)** — disimpan **di server** (sesi + kolom `flow_state` pada DB sesi), bukan di parameter klien:

```
START → S1_DONE → S2_DONE → S3_DONE → S4_DONE → SUBMITTED
```
- Setiap `POST /langkah/{n}` hanya diterima bila `flow_state == S{n-1}_DONE` (atau `n == 1`). Selain itu: *redirect* ke langkah yang sah berikutnya + log `WARN flow_violation`.
- Mundur diperbolehkan (mengubah data) namun **menurunkan** `flow_state` ke langkah tersebut sehingga langkah setelahnya harus ditinjau ulang bila data hulu berubah (mencegah konsistensi basi).
- `POST /kirim` hanya diterima pada `S4_DONE` dengan `_flow` valid dan `idem_key` belum terpakai.

**Transaksi atomik (V2.3.3)** — penyimpanan laporan:
```php
$pdo->beginTransaction();
try {
    $regNo = $counter->next($pdo);           // SELECT ... FOR UPDATE pada tabel counters
    $laporanId = $repo->insertLaporan($pdo, $data, $regNo);
    $repo->insertAnggota($pdo, $laporanId, $data['anggota']);
    $repo->insertLuaran($pdo, $laporanId, $data['luaran']);
    $idem->markUsed($pdo, $idemKey, $laporanId);   // UNIQUE constraint
    $audit->log($pdo, 'LAPORAN_SUBMITTED', $laporanId);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    // log internal, tampilkan pesan generik
}
```
Atau semuanya sukses, atau seluruhnya di-*rollback* ke keadaan semula. Mesin DB wajib **InnoDB**.

**Penguncian logika bisnis (V2.3.4)** — walau aplikasi tidak memesan kursi, ada sumber daya terbatas yang rawan *race condition*:
1. **Nomor registrasi berurutan** (`LRP-2026-000123`): baris `counters` dikunci dengan `SELECT ... FOR UPDATE` dalam transaksi yang sama.
2. **Kuota harian global (LIM-03)** dan **kuota per NIP (LIM-02)**: dihitung di dalam transaksi dengan kunci agar dua request paralel tidak sama-sama lolos.
3. **Idempotensi kirim**: `UNIQUE(idem_key)` + `INSERT` terlebih dahulu; duplikat mengembalikan hasil sebelumnya, bukan membuat laporan baru.
4. **Duplikasi konten**: `UNIQUE(payload_hash)` dalam jendela waktu (dicek di transaksi).

**Persetujuan multi-pengguna (V2.3.5, Level 3, fitur opsional `ENABLE_DUAL_APPROVAL=true`)**:
- Laporan dengan `biaya_rupiah ≥ Rp 100.000.000` memiliki `requires_dual_approval = 1`.
- Status `DISETUJUI` hanya tercapai setelah **dua admin berbeda** (`approver_1_id ≠ approver_2_id`, keduanya berbeda dari reviewer yang mengubah data) menyetujui; setiap persetujuan tercatat di tabel `approvals`.
- Perubahan data setelah persetujuan pertama **mereset** persetujuan.

### 6.6 Anti-Otomasi (V2.4.x)

| ID | Kontrol | Detail | ASVS |
|---|---|---|---|
| AA-01 | **Rate limiting** berbasis DB (sliding window) per IP & per sesi pada endpoint `POST` | Lihat LIM-01, LIM-04; header `Retry-After` | 2.4.1 |
| AA-02 | **Honeypot** field tersembunyi (`_hp_website`) | Terisi → tolak diam-diam dengan respons sukses palsu + log | 2.4.1 |
| AA-03 | **Batas ukuran & jumlah parameter** | `max_input_vars=200`, body ≤ 64 KB | 2.4.1 |
| AA-04 | **CAPTCHA opsional** (Cloudflare Turnstile/hCaptcha) pada langkah 5 | Flag `ENABLE_CAPTCHA`; verifikasi token di server | 2.4.1 |
| AA-05 | **Waktu minimal manusiawi** | Tiap langkah ≥ **5 detik** sejak form ditampilkan; total dari Langkah 1 hingga Kirim ≥ **60 detik**; ditandatangani HMAC di `_flow` sehingga tidak bisa dipalsukan klien | 2.4.2 |
| AA-06 | **Waktu maksimal** | `_flow` kedaluwarsa setelah 2 jam | 2.4.1 |
| AA-07 | **Penundaan progresif** | Kegagalan berulang meningkatkan jeda (1s, 2s, 4s…, maks 60s) | 2.4.1 |
| AA-08 | **Pelacakan perangkat ringan** | Hash (IP /24 + User-Agent) hanya untuk korelasi limit, bukan pelacakan lintas-situs | 2.4.1 |

## 7. Matriks Keterlacakan ASVS 5.0.0 — Bab V2

Level target: **L2 penuh**; L3 untuk 2.3.5 dan 2.4.2 (diaktifkan).

| ASVS | Level | Persyaratan (ringkas) | Implementasi di produk ini | Bagian PRD | Verifikasi |
|---|---|---|---|---|---|
| **2.1.1** | 1 | Dokumentasi mendefinisikan aturan validasi struktur data (email, telepon, dll.) | Tabel aturan per field (regex, rentang, enum) | §6.2 | Review dokumen; uji unit seluruh aturan |
| **2.1.2** | 2 | Dokumentasi mendefinisikan konsistensi logis antar-data | CX-01…CX-08 | §6.3 | Uji unit kombinasi field |
| **2.1.3** | 2 | Dokumentasi batas bisnis per-pengguna dan global | LIM-01…LIM-10 | §6.4 | Uji integrasi batas |
| **2.2.1** | 1 | Validasi allow-list/struktur; L2: semua input | Skema validasi seluruh field & parameter; kunci tak dikenal ditolak | §6.1, §6.2 | Uji fuzz + uji penolakan nilai tak dikenal |
| **2.2.2** | 1 | Validasi di trusted service layer; klien bukan kontrol keamanan | Class `Validator` server; HTML5/JS hanya UX; validasi ulang saat Kirim | §6.1, CX-08 | Uji dengan JS dimatikan & permintaan `curl` langsung |
| **2.2.3** | 2 | Kombinasi data terkait wajar | CX-02…CX-06 | §6.3 | Uji unit |
| **2.3.1** | 1 | Alur berurutan, tidak boleh lompat langkah | Mesin status sisi server (`flow_state`) + `_flow` | §6.5 | Uji lompat URL/POST langsung ke langkah n |
| **2.3.2** | 2 | Batas bisnis diterapkan sesuai dokumentasi | `LimitService` membaca `config/limits.php` | §6.4 | Uji beban & batas |
| **2.3.3** | 2 | Transaksi: sukses seluruhnya atau rollback | PDO transaction pada penyimpanan laporan | §6.5 | Uji kegagalan paksa di tengah insert |
| **2.3.4** | 2 | Penguncian untuk sumber daya terbatas | `FOR UPDATE` counter & kuota; `UNIQUE` idempotensi | §6.5 | Uji kirim paralel |
| **2.3.5** | 3 | Persetujuan multi-pengguna untuk alur bernilai tinggi | Dual approval laporan biaya ≥ Rp 100 juta | §6.5 | Uji peran & urutan persetujuan |
| **2.4.1** | 2 | Kontrol anti-otomasi terhadap panggilan berlebihan | Rate limit, honeypot, CAPTCHA opsional, batas body | §6.6 | Uji skrip otomatis |
| **2.4.2** | 3 | Waktu realistis manusia antar-transaksi | Min. 5 dtk/langkah, 60 dtk total, HMAC | §6.6 | Uji kirim instan |

## 8. Arsitektur dan Spesifikasi Teknis

### 8.1 Stack
| Lapisan | Pilihan |
|---|---|
| Bahasa | PHP **8.2+** (`declare(strict_types=1)` di semua file) |
| Web server | Apache 2.4 (mod_rewrite) **atau** Nginx + PHP-FPM |
| Database | MySQL 8 / MariaDB 10.6+, **InnoDB**, `utf8mb4` |
| Akses DB | **PDO**, *prepared statements*, `ATTR_EMULATE_PREPARES=false`, `ERRMODE_EXCEPTION` |
| Frontend | HTML5, CSS3 (variabel CSS), **JavaScript vanilla** (tanpa build step), font Inter |
| Dependensi (Composer, opsional) | `vlucas/phpdotenv`, `dompdf/dompdf` (US-10), PHPUnit (dev) |
| Pengujian | PHPUnit, uji HTTP dengan `curl`/Playwright untuk alur |

Aplikasi sengaja dibuat **tanpa framework** agar ringan dan mudah dipelajari; pola yang dipakai: *front controller* + router sederhana + service layer.

### 8.2 Struktur Direktori
```
simlap-riset/
├── public/                      # Document root (satu-satunya yang terekspos)
│   ├── index.php                # Front controller
│   ├── assets/
│   │   ├── css/app.css          # Tokens, layout split-card, komponen
│   │   ├── js/app.js            # Wizard UX, tabel dinamis, validasi klien (UX only)
│   │   ├── img/                 # bubble-3d.png, wave-pattern.svg
│   │   └── fonts/               # Inter (self-hosted)
├── src/
│   ├── Core/                    # Router, Request, Response, Session, View, Csrf
│   ├── Controllers/             # WizardController, SubmitController, Admin/*
│   ├── Validation/              # Validator, Rules/*, Normalizer, CrossFieldRules
│   ├── Services/                # FlowService, LimitService, RateLimiter,
│   │                            # IdempotencyService, CounterService, ApprovalService
│   ├── Repositories/            # LaporanRepository, AuditRepository
│   └── Support/                 # Hmac, Uuid, Logger
├── templates/                   # Layout & partial (form langkah 1–5, sukses, admin)
├── config/                      # app.php, limits.php, validation.php, db.php
├── database/
│   ├── schema.sql
│   └── seed_admin.php
├── storage/logs/                # di luar public, tidak dapat diakses web
├── tests/                       # Unit & integration
├── .env.example
└── PRD.md
```

### 8.3 Rute
| Metode | Path | Fungsi |
|---|---|---|
| GET | `/` | Halaman awal → memulai/melanjutkan wizard |
| GET | `/langkah/{1-5}` | Menampilkan langkah (menolak bila urutan tidak sah) |
| POST | `/langkah/{1-4}` | Validasi & simpan data langkah ke sesi server |
| POST | `/kirim` | Validasi ulang total + transaksi simpan |
| GET | `/sukses/{reg_no}` | Konfirmasi (hanya sesi yang mengirim) |
| GET | `/laporan/{reg_no}/pdf` | (P2) Unduh PDF |
| GET/POST | `/admin/login`, `/admin/logout` | Autentikasi admin |
| GET | `/admin/laporan`, `/admin/laporan/{id}` | Daftar & detail |
| POST | `/admin/laporan/{id}/setujui`, `/tolak` | Perubahan status / persetujuan |

### 8.4 Skema Basis Data (ringkas)
```sql
CREATE TABLE laporan (
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reg_no              VARCHAR(20)  NOT NULL UNIQUE,
  ketua_nama          VARCHAR(100) NOT NULL,
  ketua_jabatan       VARCHAR(100) NOT NULL,
  ketua_nip           CHAR(18)     NOT NULL,
  ketua_fakultas      VARCHAR(150) NOT NULL,
  ketua_kelompok_keilmuan VARCHAR(150) NOT NULL,
  ketua_telp          VARCHAR(16)  NOT NULL,
  ketua_email         VARCHAR(254) NOT NULL,
  lama_minggu         SMALLINT UNSIGNED NOT NULL,
  biaya_rupiah        BIGINT UNSIGNED   NOT NULL,
  jenis_penelitian    ENUM('dasar','terapan') NOT NULL,
  cpl_prodi           TEXT NOT NULL,
  tema_rip            VARCHAR(500) NOT NULL,
  mitra               VARCHAR(300) NULL,
  potensi_adopsi      TEXT NOT NULL,
  status              ENUM('DIAJUKAN','DITINJAU','DISETUJUI','DITOLAK') NOT NULL DEFAULT 'DIAJUKAN',
  requires_dual_approval TINYINT(1) NOT NULL DEFAULT 0,
  payload_hash        CHAR(64) NOT NULL,
  idem_key            CHAR(36) NOT NULL UNIQUE,
  ip_hash             CHAR(64) NOT NULL,
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_nip_created (ketua_nip, created_at),
  INDEX idx_hash_created (payload_hash, created_at),
  CONSTRAINT chk_lama  CHECK (lama_minggu BETWEEN 1 AND 104),
  CONSTRAINT chk_biaya CHECK (biaya_rupiah BETWEEN 1 AND 5000000000)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE laporan_anggota (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  laporan_id  BIGINT UNSIGNED NOT NULL,
  no_urut     TINYINT UNSIGNED NOT NULL,
  nama        VARCHAR(100) NOT NULL,
  nip_npm     VARCHAR(18)  NOT NULL,
  bidang_kelas VARCHAR(100) NOT NULL,
  UNIQUE KEY uq_laporan_nip (laporan_id, nip_npm),
  FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE laporan_luaran (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  laporan_id  BIGINT UNSIGNED NOT NULL,
  no_urut     TINYINT UNSIGNED NOT NULL,
  jenis       ENUM('jurnal_nasional','jurnal_internasional','prosiding','hki',
                   'buku','prototipe_produk','laporan_teknis','lainnya') NOT NULL,
  nama        VARCHAR(200) NULL,
  jumlah      TINYINT UNSIGNED NOT NULL,
  FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE counters (name VARCHAR(40) PRIMARY KEY, value BIGINT UNSIGNED NOT NULL) ENGINE=InnoDB;

CREATE TABLE rate_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bucket VARCHAR(80) NOT NULL,        -- mis. 'submit:ip:<hash>'
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bucket_time (bucket, created_at)
) ENGINE=InnoDB;

CREATE TABLE admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,   -- password_hash(PASSWORD_ARGON2ID)
  role ENUM('reviewer','approver') NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE approvals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  laporan_id BIGINT UNSIGNED NOT NULL,
  admin_id INT UNSIGNED NOT NULL,
  decision ENUM('SETUJU','TOLAK') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_laporan_admin (laporan_id, admin_id),   -- satu admin satu suara
  FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE,
  FOREIGN KEY (admin_id) REFERENCES admin_users(id)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event VARCHAR(50) NOT NULL,
  laporan_id BIGINT UNSIGNED NULL,
  actor VARCHAR(60) NULL,
  ip_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```
Batasan DB (`CHECK`, `UNIQUE`, `ENUM`, FK) adalah **lapisan pertahanan kedua**; sumber kebenaran tetap validator server.

### 8.5 Contoh Kerangka Validator (acuan implementasi)
```php
<?php
declare(strict_types=1);

final class Normalizer {
    public static function text(?string $v, bool $multiline = false): string {
        $v = (string)$v;
        if (!mb_check_encoding($v, 'UTF-8')) { return ''; }
        $v = \Normalizer::normalize($v, \Normalizer::FORM_C) ?: '';
        $v = preg_replace($multiline ? '/[^\P{C}\n]+/u' : '/\p{C}+/u', '', $v) ?? '';
        $v = trim(preg_replace('/[ \t]{2,}/u', ' ', $v) ?? '');
        return $v;
    }
    public static function phoneId(string $v): string {
        $d = preg_replace('/[^\d+]/', '', $v) ?? '';
        if (str_starts_with($d, '+62')) { return $d; }
        if (str_starts_with($d, '62'))  { return '+' . $d; }
        if (str_starts_with($d, '0'))   { return '+62' . substr($d, 1); }
        return $d;
    }
    public static function rupiah(string $v): string {
        return preg_replace('/[^\d]/', '', $v) ?? '';
    }
}

final class KetuaStepValidator {
    private const NAME = '/^[\p{L}\p{M}][\p{L}\p{M}\s.,\'’\-]{2,99}$/u';

    /** @return array{data: array<string,string>, errors: array<string,string>} */
    public function validate(array $in): array {
        $allowed = ['ketua_nama','ketua_jabatan','ketua_nip','ketua_fakultas',
                    'ketua_kelompok_keilmuan','ketua_telp','ketua_email',
                    '_csrf','_flow','_hp_website'];
        $errors = [];
        foreach (array_diff(array_keys($in), $allowed) as $unknown) {
            $errors['_form'] = 'Permintaan tidak valid.';     // kunci tak dikenal ditolak
        }
        $d = [
          'ketua_nama'  => Normalizer::text($in['ketua_nama'] ?? ''),
          'ketua_nip'   => preg_replace('/\s+/', '', (string)($in['ketua_nip'] ?? '')),
          'ketua_telp'  => Normalizer::phoneId((string)($in['ketua_telp'] ?? '')),
          'ketua_email' => mb_strtolower(Normalizer::text($in['ketua_email'] ?? '')),
          // ... field lain
        ];
        if (!preg_match(self::NAME, $d['ketua_nama'])) {
            $errors['ketua_nama'] = 'Nama lengkap 3–100 karakter, hanya huruf dan tanda baca nama/gelar.';
        }
        if (!preg_match('/^\d{18}$/', $d['ketua_nip'])) {
            $errors['ketua_nip'] = 'NIP harus 18 digit angka.';
        }
        if (!preg_match('/^\+628[1-9][0-9]{7,11}$/', $d['ketua_telp'])) {
            $errors['ketua_telp'] = 'Nomor telepon Indonesia tidak valid.';
        }
        if (strlen($d['ketua_email']) > 254
            || filter_var($d['ketua_email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['ketua_email'] = 'Alamat e-mail tidak valid.';
        }
        return ['data' => $d, 'errors' => $errors];
    }
}
```

## 9. Persyaratan Non-Fungsional

### 9.1 Keamanan Pendukung (diperlukan agar kontrol V2 bermakna)
| Kontrol | Persyaratan |
|---|---|
| Output encoding | Semua variabel di template lewat helper `e()` = `htmlspecialchars($v, ENT_QUOTES \| ENT_SUBSTITUTE, 'UTF-8')`; tidak ada `echo` mentah |
| SQL | Hanya prepared statements PDO; tidak ada konkatenasi SQL |
| CSRF | Token per sesi pada semua `POST`; cookie `SameSite=Lax` |
| Sesi | `session.use_strict_mode=1`, `cookie_httponly`, `cookie_secure` (HTTPS), regenerasi ID saat mulai wizard & login admin |
| Header | `Content-Security-Policy` ketat (tanpa inline script; gunakan nonce bila perlu), `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: DENY`, HSTS |
| Password admin | `password_hash(PASSWORD_ARGON2ID)`, panjang ≥ 12, rate limit login |
| Error | `display_errors=Off`; log internal di `storage/logs`; respons generik |
| Rahasia | Kredensial DB & kunci HMAC di `.env` di luar *document root* |
| Privasi | NIP, telepon, email adalah data pribadi: akses admin saja; log tidak memuat nilai PII; IP disimpan sebagai hash bergaram |
| Keamanan XML/JSON | Bila ada API JSON di masa depan, gunakan validasi skema; DTD dinonaktifkan (catatan ASVS V2.2) |

### 9.2 Kinerja dan Keandalan
- Waktu respons p95 ≤ 500 ms (tanpa PDF).
- Mampu 50 pengguna bersamaan pada VPS 2 vCPU/2 GB.
- Pencadangan DB harian; skrip pembersihan `rate_limits` > 24 jam via cron.

### 9.3 Kompatibilitas
Chrome, Edge, Firefox, Safari (2 versi mayor terakhir); iOS Safari & Chrome Android. JavaScript **tidak wajib** untuk fungsi inti (tabel dinamis memiliki *fallback* baris statis lewat tombol "Tambah baris" berbasis form POST).

### 9.4 Logging dan Audit
Peristiwa yang dicatat: `FLOW_VIOLATION`, `VALIDATION_FAIL` (nama field, bukan nilainya), `RATE_LIMITED`, `HONEYPOT_HIT`, `TOO_FAST`, `LAPORAN_SUBMITTED`, `ADMIN_LOGIN`, `APPROVAL_*`.

## 10. Rencana Pengujian

### 10.1 Pengujian Otomatis Berbasis Requirement V2
| ID | Skenario | Hasil yang diharapkan | ASVS |
|---|---|---|---|
| T-01 | NIP ketua 17 digit / berisi huruf | Ditolak, pesan di field | 2.1.1, 2.2.1 |
| T-02 | `jenis_penelitian=lainnya` (di luar enum) | Ditolak | 2.2.1 |
| T-03 | Parameter ekstra `is_admin=1` pada POST | Ditolak / diabaikan, log | 2.2.1 |
| T-04 | Kirim POST dengan JS dimatikan & data invalid | Ditolak server | 2.2.2 |
| T-05 | Manipulasi sesi/hidden field untuk melewati langkah | Ditolak, redirect | 2.2.2, 2.3.1 |
| T-06 | `terapan` tanpa mitra | Ditolak (CX-02) | 2.2.3 |
| T-07 | `lama=4` minggu dengan luaran total 30 | Ditolak (CX-05) | 2.2.3 |
| T-08 | Anggota dengan NIP sama dengan ketua | Ditolak (CX-01) | 2.2.3 |
| T-09 | `POST /langkah/3` sebelum langkah 1 | Redirect ke langkah 1 | 2.3.1 |
| T-10 | 6 kiriman dalam 1 jam dari IP sama | Kiriman ke-6 → 429 | 2.1.3, 2.3.2, 2.4.1 |
| T-11 | Dua `POST /kirim` paralel dengan `idem_key` sama | 1 laporan tersimpan | 2.3.4 |
| T-12 | Gagal insert luaran (dipaksa) | Tidak ada baris di `laporan`/`anggota` (rollback) | 2.3.3 |
| T-13 | Dua kiriman paralel meminta nomor registrasi | Nomor berbeda & berurutan, tanpa celah/duplikat | 2.3.4 |
| T-14 | Payload identik dikirim ulang | Ditolak duplikat (LIM-09) | 2.3.2 |
| T-15 | Biaya ≥ Rp 100 juta, disetujui satu admin | Status belum `DISETUJUI` | 2.3.5 |
| T-16 | Admin yang sama menyetujui dua kali | Ditolak (UNIQUE) | 2.3.5 |
| T-17 | Honeypot terisi | Ditolak, log | 2.4.1 |
| T-18 | Kirim < 60 detik sejak langkah 1 | Ditolak (AA-05) | 2.4.2 |
| T-19 | `_flow` dimodifikasi / kedaluwarsa | Ditolak | 2.4.2 |
| T-20 | Body request 100 KB | HTTP 413 | 2.4.1 |

### 10.2 Pengujian UI
- Kesesuaian visual dengan referensi (review desain berdampingan, toleransi warna ±5%).
- Responsif pada 360, 768, 1024, 1440 px.
- Uji keyboard & pembaca layar (NVDA/VoiceOver) pada alur lengkap.

### 10.3 Definition of Done
Semua uji T-01…T-20 lulus; tidak ada temuan High/Critical pada pemindaian ZAP dasar; kelengkapan field vs docx 100%; seluruh baris matriks §7 memiliki bukti verifikasi.

## 11. Rencana Rilis

| Fase | Durasi | Hasil |
|---|---|---|
| 0 — Persiapan | 2 hari | Konfirmasi asumsi §14, aset desain, setup repo & DB |
| 1 — Fondasi | 3 hari | Router, sesi, CSRF, layout split-card, tokens CSS |
| 2 — Wizard & Validasi | 5 hari | Langkah 1–5, validator, aturan CX, mesin status |
| 3 — Logika Bisnis | 4 hari | Transaksi, counter, rate limit, idempotensi, anti-otomasi |
| 4 — Admin | 3 hari | Login, daftar/detail, status, dual approval |
| 5 — QA & Hardening | 4 hari | Uji T-01…T-20, aksesibilitas, perbaikan |
| 6 — Rilis | 1 hari | Deploy, monitoring, serah terima |
| (P2) PDF | 3 hari | Ekspor PDF sesuai format docx |

## 12. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Aturan CX bisnis tidak sesuai kebijakan institusi | Penolakan data sah | Konfigurasi di `config/validation.php`; validasi pemangku kepentingan sebelum fase 2 |
| Bot membanjiri form publik | Data sampah, kuota habis | Rate limit, honeypot, waktu minimal, CAPTCHA opsional |
| Race condition kuota/nomor registrasi | Duplikasi/kelewat batas | Transaksi + `FOR UPDATE` + UNIQUE |
| Validasi hanya di klien oleh developer | Bypass | Review kode wajib; uji T-04 otomatis di CI |
| Pengguna asli terblokir oleh limit IP kampus (NAT) | Frustrasi | Limit per-IP longgar + per-NIP; pesan jelas & `Retry-After` |
| Kebocoran PII | Pelanggaran privasi | Akses admin saja, log tanpa PII, HTTPS wajib |

## 13. Glosarium
- **ASVS** — OWASP Application Security Verification Standard.
- **CPL** — Capaian Pembelajaran Lulusan.
- **RIP** — Rencana Induk Penelitian.
- **Luaran** — Output/hasil yang ditargetkan dari penelitian.
- **NIP/NPM** — Nomor Induk Pegawai / Nomor Pokok Mahasiswa.
- **Trusted service layer** — Lapisan server yang tidak dapat dimanipulasi pengguna.
- **Idempotensi** — Permintaan yang sama berulang menghasilkan efek yang sama dengan sekali.

## 14. Asumsi dan Pertanyaan Terbuka

Hal-hal berikut tidak ditentukan oleh docx maupun ASVS, sehingga PRD memakai nilai awal yang wajar. Mohon dikonfirmasi:

1. **Panjang NIP**: 18 digit (NIP ASN) — apakah ada format lain (mis. NIDN 10 digit)?
2. **NPM anggota**: rentang 8–15 digit sudah sesuai kampus?
3. **Rentang lama penelitian (1–104 minggu)** dan **batas biaya (maks Rp 5 miliar)**.
4. **Ambang persetujuan ganda** Rp 100 juta, serta aturan CX-02 s.d. CX-06.
5. **Daftar jenis luaran** (§6.2-D) — sesuaikan dengan pedoman penelitian institusi.
6. Dokumen docx **tidak memiliki field judul penelitian**; PRD tidak menambahkannya. Apakah perlu ditambahkan agar admin mudah mengidentifikasi laporan?
7. Apakah pelapor perlu **akun/login** (SSO kampus) pada versi berikutnya agar dapat melihat riwayat laporan?
8. Kontak "Butuh bantuan?" pada UI (nomor WhatsApp/email LPPM) — nilai sebenarnya.
9. Apakah **CAPTCHA** (Turnstile/hCaptcha) boleh digunakan (layanan pihak ketiga)?

---
*Akhir dokumen PRD v1.0*
