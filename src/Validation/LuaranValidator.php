<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Langkah 4 — Target Luaran. Aturan: PRD §6.2-D, CX-05, CX-06.
 */
final class LuaranValidator
{
    /**
     * @return array{data: array<int,array<string,mixed>>, errors: array<string,mixed>}
     */
    public static function validate(array $input, string $jenisPenelitian, int $lamaMinggu): array
    {
        $cfg = (array) config('validation');
        $patterns = (array) $cfg['patterns'];
        $lengths = (array) $cfg['lengths'];
        $rowCfg = (array) $cfg['rows'];
        $types = (array) $cfg['luaran_types'];

        $rawRows = $input['luaran'] ?? [];
        $errors = [];

        if (!is_array($rawRows)) {
            return ['data' => [], 'errors' => ['_form' => 'Format data luaran tidak valid.']];
        }

        $rawRows = array_values(array_filter($rawRows, static fn ($row): bool => is_array($row)));

        if (count($rawRows) < (int) $rowCfg['luaran_min']) {
            return ['data' => [], 'errors' => ['luaran' => 'Minimal 1 target luaran harus diisi.']];
        }
        if (count($rawRows) > (int) $rowCfg['luaran_max']) {
            return ['data' => [], 'errors' => ['luaran' => 'Maksimal 10 target luaran dalam satu laporan.']];
        }

        $data = [];
        $seen = [];

        foreach ($rawRows as $i => $row) {
            $jenis = Normalizer::lower($row['jenis'] ?? null);
            $nama = Normalizer::text($row['nama'] ?? null);
            $jumlahDigits = Normalizer::digits($row['jumlah'] ?? null);

            $rowData = [
                'jenis'  => $jenis,
                'nama'   => $nama,
                'jumlah' => preg_match($patterns['jumlah'], $jumlahDigits) ? (int) $jumlahDigits : 0,
            ];

            if (!array_key_exists($jenis, $types)) {
                $errors['luaran'][$i]['jenis'] = 'Pilih jenis luaran dari daftar.';
            }

            if ($jenis === 'lainnya') {
                $namaLen = Normalizer::length($nama);
                if ($namaLen < $lengths['luaran_nama']['min'] || $namaLen > $lengths['luaran_nama']['max']) {
                    $errors['luaran'][$i]['nama'] = 'Nama luaran 3–200 karakter.';
                }
            } elseif ($nama !== '') {
                $namaLen = Normalizer::length($nama);
                if ($namaLen < $lengths['luaran_nama']['min'] || $namaLen > $lengths['luaran_nama']['max']) {
                    $errors['luaran'][$i]['nama'] = 'Nama luaran 3–200 karakter.';
                }
            }

            if (!preg_match($patterns['jumlah'], $jumlahDigits)
                || $rowData['jumlah'] < 1
                || $rowData['jumlah'] > 99) {
                $errors['luaran'][$i]['jumlah'] = 'Jumlah 1–99.';
            }

            // Duplikasi kombinasi (jenis + nama ternormalisasi)
            $key = $jenis . '|' . mb_strtolower($nama, 'UTF-8');
            if (array_key_exists($jenis, $types)) {
                if (isset($seen[$key])) {
                    $errors['luaran'][$i]['jenis'] = 'Luaran ini duplikat dengan baris lain.';
                } else {
                    $seen[$key] = true;
                }
            }

            $data[] = $rowData;
        }

        // CX-05 & CX-06
        if (!isset($errors['luaran'])) {
            $cross = CrossFieldRules::checkLuaran($data, $jenisPenelitian, $lamaMinggu);
            foreach ($cross as $key => $message) {
                $errors[$key] = $message;
            }
        }

        return ['data' => $data, 'errors' => $errors];
    }
}
