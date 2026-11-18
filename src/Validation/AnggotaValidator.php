<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Langkah 2 — Anggota Tim. Aturan: PRD §6.2-B, CX-01.
 */
final class AnggotaValidator
{
    /**
     * @return array{data: array<int,array<string,string>>, errors: array<string,mixed>}
     */
    public static function validate(array $input, string $ketuaNip): array
    {
        $cfg = (array) config('validation');
        $patterns = (array) $cfg['patterns'];
        $lengths = (array) $cfg['lengths'];
        $rowCfg = (array) $cfg['rows'];

        $rawRows = $input['anggota'] ?? [];
        $errors = [];

        if (!is_array($rawRows)) {
            return ['data' => [], 'errors' => ['_form' => 'Format data anggota tidak valid.']];
        }

        $rawRows = array_values(array_filter($rawRows, static fn ($row): bool => is_array($row)));

        if (count($rawRows) < (int) $rowCfg['anggota_min']) {
            return ['data' => [], 'errors' => ['anggota' => 'Minimal 1 anggota tim harus diisi.']];
        }
        if (count($rawRows) > (int) $rowCfg['anggota_max']) {
            return ['data' => [], 'errors' => ['anggota' => 'Maksimal 10 anggota tim dalam satu laporan.']];
        }

        $data = [];
        $seenNip = [];

        foreach ($rawRows as $i => $row) {
            $nama = Normalizer::text($row['nama'] ?? null);
            $nip = preg_replace('/\s+/', '', (string) ($row['nip_npm'] ?? '')) ?? '';
            $bidang = Normalizer::text($row['bidang_kelas'] ?? null);

            $rowData = [
                'nama'         => $nama,
                'nip_npm'      => $nip,
                'bidang_kelas' => $bidang,
            ];

            $namaLen = Normalizer::length($nama);
            if ($namaLen < $lengths['anggota_nama']['min']
                || $namaLen > $lengths['anggota_nama']['max']
                || !preg_match($patterns['nama'], $nama)) {
                $errors['anggota'][$i]['nama'] = 'Nama dan gelar 3–100 karakter, hanya huruf dan tanda baca gelar.';
            }

            $isNip = preg_match($patterns['nip'], $nip) === 1;
            $isNpm = preg_match($patterns['npm'], $nip) === 1;
            if (!$isNip && !$isNpm) {
                $errors['anggota'][$i]['nip_npm'] = 'Isi NIP (18 digit) atau NPM (8–15 digit).';
            } else {
                if ($nip === $ketuaNip) {
                    $errors['anggota'][$i]['nip_npm'] = 'NIP/NPM tidak boleh sama dengan NIP ketua tim.';
                } elseif (isset($seenNip[$nip])) {
                    $errors['anggota'][$i]['nip_npm'] = 'NIP/NPM duplikat dengan baris lain.';
                } else {
                    $seenNip[$nip] = true;
                }
            }

            $bidangLen = Normalizer::length($bidang);
            if ($bidangLen < $lengths['anggota_bidang']['min']
                || $bidangLen > $lengths['anggota_bidang']['max']
                || !preg_match($patterns['teks_pendek'], $bidang)) {
                $errors['anggota'][$i]['bidang_kelas'] = 'Bidang ilmu/kelas 1–100 karakter dan tidak boleh memuat karakter khusus.';
            }

            $data[] = $rowData;
        }

        // CX-01 juga diperiksa sebagai lapisan eksplisit.
        foreach (CrossFieldRules::checkKetuaNipConflict($ketuaNip, $data) as $i => $message) {
            $errors['anggota'][$i]['nip_npm'] = $message;
        }

        return ['data' => $data, 'errors' => $errors];
    }
}
