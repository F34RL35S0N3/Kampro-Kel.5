<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Konsistensi kontekstual antar-data — ASVS V2.1.2 / V2.2.3 (PRD §6.3).
 * Setiap metode memetakan langsung ke ID aturan (CX-xx).
 */
final class CrossFieldRules
{
    /** CX-07 — domain e-mail disposable ditolak. */
    public static function checkEmailDomain(string $email): ?string
    {
        $at = strrpos($email, '@');
        if ($at === false) {
            return 'Alamat e-mail tidak valid.';
        }
        $domain = strtolower(substr($email, $at + 1));
        $disposable = (array) config('validation.disposable_domains', []);
        if (in_array($domain, $disposable, true)) {
            return 'Gunakan alamat e-mail institusi/umum yang aktif, bukan e-mail sekali pakai.';
        }
        return null;
    }

    /**
     * CX-01 — NIP ketua tidak boleh sama dengan NIP/NPM anggota.
     * @return array<int,string> error keyed by indeks baris
     */
    public static function checkKetuaNipConflict(string $ketuaNip, array $anggota): array
    {
        $errors = [];
        if ($ketuaNip === '') {
            return $errors;
        }
        foreach ($anggota as $i => $row) {
            $nip = (string) ($row['nip_npm'] ?? '');
            if ($nip !== '' && $nip === $ketuaNip) {
                $errors[(int) $i] = 'NIP/NPM tidak boleh sama dengan NIP ketua tim.';
            }
        }
        return $errors;
    }

    /**
     * CX-02, CX-03, CX-04 — kewajaran rincian penelitian.
     * @return array<string,string>
     */
    public static function checkRincian(array $data, int $anggotaCount): array
    {
        $errors = [];
        $cfg = (array) config('validation.rincian');
        $jenis = (string) ($data['jenis_penelitian'] ?? '');
        $lama = (int) ($data['lama_minggu'] ?? 0);
        $biaya = (int) ($data['biaya_rupiah'] ?? 0);
        $mitra = trim((string) ($data['mitra'] ?? ''));
        $potensi = (string) ($data['potensi_adopsi'] ?? '');

        $mitraKosong = $mitra === '' || $mitra === '-' || strcasecmp($mitra, 'tidak ada') === 0;

        // CX-02 — penelitian terapan wajib mitra & potensi adopsi memadai
        if ($jenis === 'terapan') {
            if ($mitraKosong) {
                $errors['mitra'] = 'Penelitian terapan wajib mencantumkan mitra penelitian.';
            }
            if (Normalizer::length($potensi) < (int) $cfg['terapan_potensi_min']) {
                $errors['potensi_adopsi'] = 'Untuk penelitian terapan, potensi adopsi minimal 30 karakter.';
            }
        }

        // CX-03 — biaya tinggi wajib mitra dan minimal 2 anggota
        if ($biaya >= (int) $cfg['mitra_wajib_biaya']) {
            if ($mitraKosong) {
                $errors['mitra'] = 'Biaya ≥ Rp 100 juta wajib mencantumkan mitra penelitian.';
            }
            if ($anggotaCount < 2) {
                $errors['_form'] = 'Biaya ≥ Rp 100 juta membutuhkan minimal 2 anggota tim.';
            }
        }

        // CX-04 — kewajaran biaya per minggu
        if ($lama > 0 && $biaya > 0) {
            $perWeek = $biaya / $lama;
            if ($perWeek < (int) $cfg['biaya_per_minggu_min']
                || $perWeek > (int) $cfg['biaya_per_minggu_max']) {
                $errors['biaya_rupiah'] = sprintf(
                    'Biaya per minggu tidak wajar (saat ini sekitar %s/minggu; kisaran wajar Rp 10.000–Rp 500.000.000).',
                    number_format((int) $perWeek, 0, ',', '.')
                );
            }
        }

        return $errors;
    }

    /**
     * CX-05, CX-06 — kewajaran target luaran terhadap durasi & jenis penelitian.
     * @return array{_form?:string,total?:string,dasar?:string}
     */
    public static function checkLuaran(array $luaran, string $jenisPenelitian, int $lamaMinggu): array
    {
        $errors = [];
        $total = 0;
        foreach ($luaran as $row) {
            $total += (int) ($row['jumlah'] ?? 0);
        }

        // CX-05 — batas total luaran terhadap durasi
        $cap = 50;
        foreach ((array) config('validation.luaran_duration_caps', []) as $rule) {
            if ($lamaMinggu <= (int) $rule['max_weeks']) {
                $cap = (int) $rule['max_total'];
                break;
            }
        }
        if ($total > $cap) {
            $errors['total'] = sprintf(
                'Total luaran (%d) melebihi batas wajar untuk durasi %d minggu (maksimal %d).',
                $total,
                $lamaMinggu,
                $cap
            );
        }

        // CX-06 — penelitian dasar wajib menghasilkan luaran akademik
        if ($jenisPenelitian === 'dasar') {
            $allowed = (array) config('validation.dasar_required_outputs', []);
            $found = false;
            foreach ($luaran as $row) {
                if (in_array((string) ($row['jenis'] ?? ''), $allowed, true)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $errors['dasar'] = 'Penelitian dasar wajib menargetkan minimal satu jurnal atau prosiding.';
            }
        }

        return $errors;
    }

    /** LIM-08 — panjang total teks laporan. */
    public static function totalTextLength(array $data): int
    {
        $total = 0;
        $scalarKeys = [
            'ketua_nama', 'ketua_jabatan', 'ketua_nip', 'ketua_fakultas',
            'ketua_kelompok_keilmuan', 'ketua_telp', 'ketua_email',
            'cpl_prodi', 'tema_rip', 'mitra', 'potensi_adopsi',
        ];
        foreach ($scalarKeys as $key) {
            $total += Normalizer::length((string) ($data[$key] ?? ''));
        }
        foreach ((array) ($data['anggota'] ?? []) as $row) {
            $total += Normalizer::length((string) ($row['nama'] ?? ''));
            $total += Normalizer::length((string) ($row['nip_npm'] ?? ''));
            $total += Normalizer::length((string) ($row['bidang_kelas'] ?? ''));
        }
        foreach ((array) ($data['luaran'] ?? []) as $row) {
            $total += Normalizer::length((string) ($row['nama'] ?? ''));
        }
        return $total;
    }
}
