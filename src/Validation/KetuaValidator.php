<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Langkah 1 — Ketua Tim. Aturan: PRD §6.2-A, CX-07.
 */
final class KetuaValidator
{
    public const FIELDS = [
        'ketua_nama', 'ketua_jabatan', 'ketua_nip', 'ketua_fakultas',
        'ketua_kelompok_keilmuan', 'ketua_telp', 'ketua_email',
    ];

    /**
     * @return array{data: array<string,string>, errors: array<string,string>}
     */
    public static function validate(array $input): array
    {
        $cfg = (array) config('validation');
        $patterns = (array) $cfg['patterns'];
        $lengths = (array) $cfg['lengths'];
        $errors = [];

        $data = [
            'ketua_nama'              => Normalizer::text($input['ketua_nama'] ?? null),
            'ketua_jabatan'           => Normalizer::text($input['ketua_jabatan'] ?? null),
            'ketua_nip'               => preg_replace('/\s+/', '', (string) ($input['ketua_nip'] ?? '')) ?? '',
            'ketua_fakultas'          => Normalizer::text($input['ketua_fakultas'] ?? null),
            'ketua_kelompok_keilmuan' => Normalizer::text($input['ketua_kelompok_keilmuan'] ?? null),
            'ketua_telp'              => Normalizer::phoneId($input['ketua_telp'] ?? null),
            'ketua_email'             => Normalizer::email($input['ketua_email'] ?? null),
        ];

        // A. Nama lengkap
        $len = Normalizer::length($data['ketua_nama']);
        if ($len < $lengths['ketua_nama']['min'] || $len > $lengths['ketua_nama']['max']
            || !preg_match($patterns['nama'], $data['ketua_nama'])) {
            $errors['ketua_nama'] = 'Nama lengkap 3–100 karakter, hanya huruf dan tanda baca nama/gelar.';
        }

        // B. Jabatan
        if (!preg_match($patterns['teks'], $data['ketua_jabatan'])
            || Normalizer::length($data['ketua_jabatan']) < $lengths['ketua_jabatan']['min']
            || Normalizer::length($data['ketua_jabatan']) > $lengths['ketua_jabatan']['max']) {
            $errors['ketua_jabatan'] = 'Jabatan 2–100 karakter dan tidak boleh memuat karakter khusus.';
        }

        // C. NIP — tepat 18 digit
        if (!preg_match($patterns['nip'], $data['ketua_nip'])) {
            $errors['ketua_nip'] = 'NIP harus 18 digit angka.';
        }

        // D. Fakultas / Sekolah
        if (!preg_match($patterns['teks'], $data['ketua_fakultas'])
            || Normalizer::length($data['ketua_fakultas']) < $lengths['ketua_fakultas']['min']
            || Normalizer::length($data['ketua_fakultas']) > $lengths['ketua_fakultas']['max']) {
            $errors['ketua_fakultas'] = 'Fakultas/Sekolah 2–150 karakter dan tidak boleh memuat karakter khusus.';
        }

        // E. Kelompok Keilmuan
        if (!preg_match($patterns['teks'], $data['ketua_kelompok_keilmuan'])
            || Normalizer::length($data['ketua_kelompok_keilmuan']) < $lengths['ketua_kelompok_keilmuan']['min']
            || Normalizer::length($data['ketua_kelompok_keilmuan']) > $lengths['ketua_kelompok_keilmuan']['max']) {
            $errors['ketua_kelompok_keilmuan'] = 'Kelompok keilmuan 2–150 karakter dan tidak boleh memuat karakter khusus.';
        }

        // F. Telp/HP Indonesia
        if (!preg_match($patterns['telp_id'], $data['ketua_telp'])) {
            $errors['ketua_telp'] = 'Nomor telepon Indonesia tidak valid (contoh: 0812 3456 7890).';
        }

        // G. E-mail
        $email = $data['ketua_email'];
        $validEmail = Normalizer::length($email) <= $lengths['ketua_email']['max']
            && $email !== ''
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        if ($validEmail) {
            $parts = explode('@', $email, 2);
            $validEmail = count($parts) === 2 && Normalizer::length($parts[0]) <= 64;
        }
        if (!$validEmail) {
            $errors['ketua_email'] = 'Alamat e-mail tidak valid.';
        }

        // CX-07 — domain email disposable
        if (!isset($errors['ketua_email'])) {
            $emailError = CrossFieldRules::checkEmailDomain($email);
            if ($emailError !== null) {
                $errors['ketua_email'] = $emailError;
            }
        }

        return ['data' => $data, 'errors' => $errors];
    }
}
