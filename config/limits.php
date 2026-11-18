<?php
declare(strict_types=1);

/*
 |--------------------------------------------------------------------------
 | Batas bisnis per-pengguna dan global — ASVS V2.1.3 / V2.3.2 (PRD §6.4)
 |--------------------------------------------------------------------------
 */

return [
    // LIM-01 — pengiriman laporan per IP
    'submit_per_ip'      => ['limit' => 5,    'window' => 3600],

    // LIM-02 — pengiriman laporan per NIP ketua
    'submit_per_nip'     => ['limit' => 3,    'window' => 86400],

    // LIM-03 — kuota harian global
    'submit_global'      => ['limit' => 500,  'window' => 86400],

    // LIM-04 — percobaan validasi gagal per sesi
    'validation_fail'    => ['limit' => 30,   'window' => 3600, 'lock_seconds' => 900],

    // LIM-05 — sesi wizard aktif per IP (dicatat, pembersihan oleh cron)
    'active_sessions'    => ['limit' => 10],

    // LIM-06 — ukuran body request (byte)
    'max_body_bytes'     => 65536,

    // LIM-07 — baris maksimum (lihat juga config/validation.php)
    'max_rows'           => 10,

    // LIM-08 — panjang total teks per laporan
    'max_total_text'     => 12000,

    // LIM-09 — laporan identik (payload hash) dalam jendela waktu
    'duplicate_window'   => 2592000,

    // LIM-10 — umur sesi wizard
    'session_absolute'   => 7200,
    'session_idle'       => 1800,

    // AA-05 — waktu manusiawi minimal
    'min_step_seconds'   => 5,
    'min_total_seconds'  => 60,

    // AA-06 — umur token alur
    'flow_token_ttl'     => 7200,

    // AA-07 — penundaan progresif (detik) saat gagal berulang
    'progressive_delay'  => [1, 2, 4, 8, 16, 32, 60],
];
