<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Support\Hmac;
use App\Support\Uuid;

/**
 * Mesin status alur wizard — disimpan di server (V2.3.1).
 *
 *   START → S1_DONE → S2_DONE → S3_DONE → S4_DONE → SUBMITTED
 *
 * `flow_done` merepresentasikan langkah terakhir yang sudah selesai.
 */
final class FlowService
{
    private const STEP_KEYS = [
        1 => 'ketua',
        2 => 'anggota',
        3 => 'rincian',
        4 => 'luaran',
    ];

    public static function start(): array
    {
        $now = time();
        $wizard = [
            'id'             => bin2hex(random_bytes(16)),
            'started_at'     => $now,
            'last_activity'  => $now,
            'flow_done'      => 0,
            'data'           => ['ketua' => [], 'anggota' => [], 'rincian' => [], 'luaran' => []],
            'step_issued'    => [],
            'idem_key'       => null,
            'submitted'      => [],
            'fail_count'     => 0,
            'locked_until'   => 0,
            'next_allowed_at'=> 0,
            'fingerprint'    => user_agent_hash((string) config('app.hmac_key')),
        ];
        Session::regenerate();
        Session::set('wizard', $wizard);
        return $wizard;
    }

    public static function get(): ?array
    {
        $wizard = Session::get('wizard');
        if (!is_array($wizard)) {
            return null;
        }
        $now = time();
        $absolute = (int) config('limits.session_absolute', 7200);
        $idle = (int) config('limits.session_idle', 1800);
        if ($now - (int) ($wizard['started_at'] ?? 0) > $absolute
            || $now - (int) ($wizard['last_activity'] ?? 0) > $idle) {
            Session::forget('wizard');
            return null;
        }
        return $wizard;
    }

    public static function touch(): ?array
    {
        $wizard = self::get();
        if ($wizard === null) {
            return null;
        }
        $wizard['last_activity'] = time();
        self::save($wizard);
        return $wizard;
    }

    public static function ensure(): array
    {
        $wizard = self::touch();
        if ($wizard === null) {
            $wizard = self::start();
        }
        return $wizard;
    }

    public static function save(array $wizard): void
    {
        $wizard['last_activity'] = time();
        Session::set('wizard', $wizard);
    }

    public static function currentStep(array $wizard): int
    {
        return min(((int) ($wizard['flow_done'] ?? 0)) + 1, 5);
    }

    public static function canAccess(array $wizard, int $step): bool
    {
        if ($step < 1 || $step > 5) {
            return false;
        }
        $done = (int) ($wizard['flow_done'] ?? 0);
        if ($step === 5) {
            return $done >= 4;
        }
        return $step <= $done + 1;
    }

    /** Simpan data satu langkah + turunkan/naikkan status alur sesuai aturan. */
    public static function setStepData(array &$wizard, int $step, array $data): void
    {
        $key = self::STEP_KEYS[$step] ?? null;
        if ($key === null) {
            return;
        }
        $wizard['data'][$key] = $data;
        // Mundur ke langkah lebih awal menurunkan flow_done (data hilir harus ditinjau ulang).
        $wizard['flow_done'] = $step;
        $wizard['idem_key'] = null;
    }

    public static function stepData(array $wizard, int $step): array
    {
        $key = self::STEP_KEYS[$step] ?? null;
        return $key === null ? [] : (array) ($wizard['data'][$key] ?? []);
    }

    public static function allData(array $wizard): array
    {
        $data = (array) ($wizard['data'] ?? []);
        return [
            'ketua'   => (array) ($data['ketua'] ?? []),
            'anggota' => (array) ($data['anggota'] ?? []),
            'rincian' => (array) ($data['rincian'] ?? []),
            'luaran'  => (array) ($data['luaran'] ?? []),
        ];
    }

    public static function issueFlow(array &$wizard, int $step): string
    {
        $wizard['step_issued'][$step] = time();
        return Hmac::issueFlowToken($step, Session::id());
    }

    /**
     * @return array{ok:bool, reason:?string, iat:int}
     */
    public static function verifyFlow(array $wizard, int $step, ?string $token): array
    {
        if (!is_string($token) || $token === '') {
            return ['ok' => false, 'reason' => 'missing', 'iat' => 0];
        }
        $payload = Hmac::verifyFlowToken($token, $step, Session::id());
        if ($payload === null) {
            return ['ok' => false, 'reason' => 'invalid', 'iat' => 0];
        }

        // AA-05 — waktu minimal manusiawi per langkah
        $min = (int) config('limits.min_step_seconds', 5);
        if (time() - $payload['iat'] < $min) {
            return ['ok' => false, 'reason' => 'too_fast', 'iat' => $payload['iat']];
        }

        return ['ok' => true, 'reason' => null, 'iat' => $payload['iat']];
    }

    public static function totalElapsed(array $wizard): int
    {
        return max(0, time() - (int) ($wizard['started_at'] ?? time()));
    }

    public static function ensureIdemKey(array &$wizard): string
    {
        if (empty($wizard['idem_key']) || !is_string($wizard['idem_key'])) {
            $wizard['idem_key'] = Uuid::v4();
        }
        return $wizard['idem_key'];
    }

    public static function fingerprintMatches(array $wizard): bool
    {
        // AA-08 — korelasi ringan perangkat; bukan kontrol keamanan tunggal.
        return hash_equals(
            (string) ($wizard['fingerprint'] ?? ''),
            user_agent_hash((string) config('app.hmac_key'))
        );
    }

    public static function reset(): void
    {
        Session::forget('wizard');
    }
}
