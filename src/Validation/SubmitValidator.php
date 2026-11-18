<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * CX-08 — validasi ulang total seluruh langkah saat Kirim (V2.2.2).
 * Data sesi diperlakukan sebagai tidak tepercaya.
 */
final class SubmitValidator
{
    /**
     * @return array{step:int, errors:array<string,mixed>, data:array<string,mixed>}
     */
    public static function validateAll(array $state): array
    {
        $ketuaState = (array) ($state['ketua'] ?? []);
        $anggotaState = (array) ($state['anggota'] ?? []);
        $rincianState = (array) ($state['rincian'] ?? []);
        $luaranState = (array) ($state['luaran'] ?? []);

        // Langkah 1
        $ketua = KetuaValidator::validate($ketuaState);
        if ($ketua['errors'] !== []) {
            return ['step' => 1, 'errors' => $ketua['errors'], 'data' => []];
        }

        // Langkah 2
        $anggota = AnggotaValidator::validate(['anggota' => $anggotaState], (string) $ketua['data']['ketua_nip']);
        if ($anggota['errors'] !== []) {
            return ['step' => 2, 'errors' => $anggota['errors'], 'data' => []];
        }

        // Langkah 3
        $rincian = RincianValidator::validate($rincianState, count($anggota['data']));
        if ($rincian['errors'] !== []) {
            return ['step' => 3, 'errors' => $rincian['errors'], 'data' => []];
        }

        // Langkah 4
        $luaran = LuaranValidator::validate(
            ['luaran' => $luaranState],
            (string) $rincian['data']['jenis_penelitian'],
            (int) $rincian['data']['lama_minggu']
        );
        if ($luaran['errors'] !== []) {
            return ['step' => 4, 'errors' => $luaran['errors'], 'data' => []];
        }

        $data = array_merge(
            $ketua['data'],
            $rincian['data'],
            ['anggota' => $anggota['data'], 'luaran' => $luaran['data']]
        );

        // LIM-08 — panjang total teks
        $maxTotal = (int) config('limits.max_total_text', 12000);
        if (CrossFieldRules::totalTextLength($data) > $maxTotal) {
            return [
                'step'   => 3,
                'errors' => ['_form' => 'Total panjang teks laporan melebihi batas 12.000 karakter.'],
                'data'   => [],
            ];
        }

        return ['step' => 0, 'errors' => [], 'data' => $data];
    }

    /** Hitung hash payload ternormalisasi untuk LIM-09. */
    public static function payloadHash(array $data): string
    {
        $canonical = [
            'ketua'  => [
                'nama' => $data['ketua_nama'] ?? '',
                'nip'  => $data['ketua_nip'] ?? '',
                'email'=> $data['ketua_email'] ?? '',
                'telp' => $data['ketua_telp'] ?? '',
                'jabatan' => $data['ketua_jabatan'] ?? '',
                'fakultas'=> $data['ketua_fakultas'] ?? '',
                'kelompok'=> $data['ketua_kelompok_keilmuan'] ?? '',
            ],
            'anggota' => array_map(static fn (array $row): array => [
                'nama' => $row['nama'] ?? '',
                'nip'  => $row['nip_npm'] ?? '',
                'bidang' => $row['bidang_kelas'] ?? '',
            ], (array) ($data['anggota'] ?? [])),
            'rincian' => [
                'lama'   => $data['lama_minggu'] ?? 0,
                'biaya'  => $data['biaya_rupiah'] ?? 0,
                'jenis'  => $data['jenis_penelitian'] ?? '',
                'cpl'    => $data['cpl_prodi'] ?? '',
                'tema'   => $data['tema_rip'] ?? '',
                'mitra'  => $data['mitra'] ?? '',
                'potensi'=> $data['potensi_adopsi'] ?? '',
            ],
            'luaran' => array_map(static fn (array $row): array => [
                'jenis'  => $row['jenis'] ?? '',
                'nama'   => $row['nama'] ?? '',
                'jumlah' => $row['jumlah'] ?? 0,
            ], (array) ($data['luaran'] ?? [])),
        ];

        return hash('sha256', json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
