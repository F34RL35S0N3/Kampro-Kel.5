<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Langkah 3 — Rincian Penelitian. Aturan: PRD §6.2-C, CX-02..CX-04.
 */
final class RincianValidator
{
    /**
     * @return array{data: array<string,mixed>, errors: array<string,string>}
     */
    public static function validate(array $input, int $anggotaCount): array
    {
        $cfg = (array) config('validation');
        $patterns = (array) $cfg['patterns'];
        $lengths = (array) $cfg['lengths'];
        $rincianCfg = (array) $cfg['rincian'];
        $errors = [];

        $lamaDigits = Normalizer::digits($input['lama_minggu'] ?? null);
        $biayaDigits = Normalizer::digits($input['biaya_rupiah'] ?? null);
        $jenis = Normalizer::lower($input['jenis_penelitian'] ?? null);

        $data = [
            'lama_minggu'      => preg_match($patterns['lama'], $lamaDigits) ? (int) $lamaDigits : 0,
            'biaya_rupiah'     => preg_match($patterns['biaya'], $biayaDigits) ? (int) $biayaDigits : 0,
            'jenis_penelitian' => $jenis,
            'cpl_prodi'        => Normalizer::text($input['cpl_prodi'] ?? null, true),
            'tema_rip'         => Normalizer::text($input['tema_rip'] ?? null),
            'mitra'            => Normalizer::text($input['mitra'] ?? null),
            'potensi_adopsi'   => Normalizer::text($input['potensi_adopsi'] ?? null, true),
        ];

        // Lama penelitian 1–104 minggu
        if (!preg_match($patterns['lama'], $lamaDigits)
            || $data['lama_minggu'] < (int) $rincianCfg['lama_min']
            || $data['lama_minggu'] > (int) $rincianCfg['lama_max']) {
            $errors['lama_minggu'] = 'Lama penelitian 1–104 minggu.';
        }

        // Biaya rupiah bulat 1 – 5 miliar
        if (!preg_match($patterns['biaya'], $biayaDigits)
            || $data['biaya_rupiah'] < (int) $rincianCfg['biaya_min']
            || $data['biaya_rupiah'] > (int) $rincianCfg['biaya_max']) {
            $errors['biaya_rupiah'] = 'Biaya harus angka rupiah bulat, 1 s.d. 5 miliar.';
        }

        // Enum jenis penelitian
        $allowedJenis = array_keys((array) $cfg['jenis_penelitian']);
        if (!in_array($jenis, $allowedJenis, true)) {
            $errors['jenis_penelitian'] = 'Pilih jenis penelitian.';
        }

        // CPL Program Studi
        $cplLen = Normalizer::length($data['cpl_prodi']);
        if ($cplLen < $lengths['cpl_prodi']['min']
            || $cplLen > $lengths['cpl_prodi']['max']
            || Normalizer::lineCount($data['cpl_prodi']) > 20) {
            $errors['cpl_prodi'] = 'CPL 5–1000 karakter (maksimal 20 baris).';
        }

        // Tema RIP
        $temaLen = Normalizer::length($data['tema_rip']);
        if ($temaLen < $lengths['tema_rip']['min'] || $temaLen > $lengths['tema_rip']['max']) {
            $errors['tema_rip'] = 'Tema RIP 3–500 karakter.';
        }

        // Mitra opsional 0–300 karakter
        if (Normalizer::length($data['mitra']) > $lengths['mitra']['max']) {
            $errors['mitra'] = 'Mitra maksimal 300 karakter.';
        }

        // Potensi adopsi
        $potensiLen = Normalizer::length($data['potensi_adopsi']);
        if ($potensiLen < $lengths['potensi_adopsi']['min']
            || $potensiLen > $lengths['potensi_adopsi']['max']) {
            $errors['potensi_adopsi'] = 'Potensi adopsi 10–1000 karakter.';
        }

        // CX-02..CX-04 hanya bila field dasar valid
        if (!isset($errors['lama_minggu'], $errors['biaya_rupiah'], $errors['jenis_penelitian'])) {
            $cross = CrossFieldRules::checkRincian($data, $anggotaCount);
            foreach ($cross as $field => $message) {
                $errors[$field] = $message;
            }
        }

        return ['data' => $data, 'errors' => $errors];
    }
}
