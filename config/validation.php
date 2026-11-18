<?php
declare(strict_types=1);

/*
 |--------------------------------------------------------------------------
 | Dokumentasi aturan validasi — ASVS 5.0.0 V2.1.1 / V2.2.1 (PRD §6.2)
 |--------------------------------------------------------------------------
 | Nilai di file ini adalah "sumber kebenaran" yang dipakai oleh validator
 | di src/Validation. Setiap perubahan harus disertai pembaruan PRD §6.2.
 */

return [
    // Pola allow-list (Unicode). Semua input harus cocok penuh (^...$).
    'patterns' => [
        'nama'       => '/^[\p{L}\p{M}][\p{L}\p{M}\s.,\'’\-]{2,99}$/u',
        'teks'       => '/^[\p{L}\p{M}\p{N}\s.,\/&()\'’\-]{2,150}$/u',
        'teks_pendek'=> '/^[\p{L}\p{M}\p{N}\s.,\/&()\'’\-]{1,100}$/u',
        'nip'        => '/^\d{18}$/',
        'npm'        => '/^\d{8,15}$/',
        'telp_id'    => '/^\+628[1-9][0-9]{7,11}$/',
        'lama'       => '/^\d{1,3}$/',
        'biaya'      => '/^\d{1,12}$/',
        'jumlah'     => '/^\d{1,2}$/',
    ],

    // Batas panjang
    'lengths' => [
        'ketua_nama'             => ['min' => 3,   'max' => 100],
        'ketua_jabatan'          => ['min' => 2,   'max' => 100],
        'ketua_fakultas'         => ['min' => 2,   'max' => 150],
        'ketua_kelompok_keilmuan'=> ['min' => 2,   'max' => 150],
        'ketua_email'            => ['min' => 3,   'max' => 254],
        'anggota_nama'           => ['min' => 3,   'max' => 100],
        'anggota_bidang'         => ['min' => 1,   'max' => 100],
        'cpl_prodi'              => ['min' => 5,   'max' => 1000],
        'tema_rip'               => ['min' => 3,   'max' => 500],
        'mitra'                  => ['min' => 0,   'max' => 300],
        'potensi_adopsi'         => ['min' => 10,  'max' => 1000],
        'luaran_nama'            => ['min' => 3,   'max' => 200],
    ],

    // Batas baris dinamis
    'rows' => [
        'anggota_min' => 1,
        'anggota_max' => 10,
        'luaran_min'  => 1,
        'luaran_max'  => 10,
    ],

    // Rincian penelitian
    'rincian' => [
        'lama_min'      => 1,
        'lama_max'      => 104,
        'biaya_min'     => 1,
        'biaya_max'     => 5000000000,
        'biaya_per_minggu_min' => 10000,
        'biaya_per_minggu_max' => 500000000,
        'terapan_potensi_min'  => 30,
        'mitra_wajib_biaya'    => 100000000,
        'dual_approval_threshold' => 100000000,
    ],

    'jenis_penelitian' => [
        'dasar'   => 'Dasar',
        'terapan' => 'Terapan',
    ],

    // Daftar tetap jenis luaran (allow-list)
    'luaran_types' => [
        'jurnal_nasional'      => 'Jurnal Nasional',
        'jurnal_internasional' => 'Jurnal Internasional',
        'prosiding'            => 'Prosiding',
        'hki'                  => 'HKI / Paten',
        'buku'                 => 'Buku',
        'prototipe_produk'     => 'Prototipe / Produk',
        'laporan_teknis'       => 'Laporan Teknis',
        'lainnya'              => 'Lainnya',
    ],

    // CX-05: total jumlah luaran maksimum terhadap durasi
    'luaran_duration_caps' => [
        ['max_weeks' => 8,   'max_total' => 5],
        ['max_weeks' => 24,  'max_total' => 20],
        ['max_weeks' => 999, 'max_total' => 50],
    ],

    // CX-06: untuk penelitian dasar minimal satu luaran akademik
    'dasar_required_outputs' => ['jurnal_nasional', 'jurnal_internasional', 'prosiding'],

    // CX-07: domain e-mail disposable yang ditolak
    'disposable_domains' => [
        'mailinator.com', 'tempmail.com', 'temp-mail.org', 'guerrillamail.com',
        '10minutemail.com', 'throwawaymail.com', 'yopmail.com', 'trashmail.com',
        'sharklasers.com', 'getnada.com', 'dispostable.com', 'fakeinbox.com',
        'maildrop.cc', 'mintemail.com', 'spam4.me', 'mailnesia.com',
    ],
];
