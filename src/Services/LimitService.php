<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use PDO;

/**
 * Batas bisnis per-pengguna & global — ASVS V2.1.3 / V2.3.2 (PRD §6.4).
 */
final class LimitService
{
    /**
     * Periksa LIM-01, LIM-02, LIM-03. Dipanggil di dalam transaksi submit.
     * @return array{ok:bool, reason:?string, retry:int}
     */
    public static function submissionStatus(string $ipHash, string $ketuaNip): array
    {
        $pdo = Database::pdo();
        $limiter = new RateLimiter();

        // LIM-01 — per IP
        $ipCfg = (array) config('limits.submit_per_ip');
        $ipBucket = 'submit:ip:' . $ipHash;
        if ($limiter->count($ipBucket, (int) $ipCfg['window']) >= (int) $ipCfg['limit']) {
            return ['ok' => false, 'reason' => 'ip', 'retry' => $limiter->retryAfter($ipBucket, (int) $ipCfg['window'])];
        }

        // LIM-02 — per NIP ketua
        $nipCfg = (array) config('limits.submit_per_nip');
        $sinceNip = date('Y-m-d H:i:s', time() - (int) $nipCfg['window']);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM laporan WHERE ketua_nip = ? AND created_at >= ?');
        $stmt->execute([$ketuaNip, $sinceNip]);
        if ((int) $stmt->fetchColumn() >= (int) $nipCfg['limit']) {
            return ['ok' => false, 'reason' => 'nip', 'retry' => (int) $nipCfg['window']];
        }

        // LIM-03 — kuota harian global
        $globalCfg = (array) config('limits.submit_global');
        $sinceGlobal = date('Y-m-d H:i:s', time() - (int) $globalCfg['window']);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM laporan WHERE created_at >= ?');
        $stmt->execute([$sinceGlobal]);
        if ((int) $stmt->fetchColumn() >= (int) $globalCfg['limit']) {
            return ['ok' => false, 'reason' => 'global', 'retry' => (int) $globalCfg['window']];
        }

        return ['ok' => true, 'reason' => null, 'retry' => 0];
    }

    /** Catat satu pengiriman untuk bucket IP (di dalam transaksi). */
    public static function recordSubmit(PDO $pdo, string $ipHash): void
    {
        $stmt = $pdo->prepare('INSERT INTO rate_limits (bucket, created_at) VALUES (?, ?)');
        $stmt->execute(['submit:ip:' . $ipHash, now()]);
    }

    /** Catat percobaan validasi gagal (LIM-04 + AA-07 progressive delay). */
    public static function registerValidationFailure(string $sessionId): void
    {
        $bucket = 'validate:' . hash('sha256', $sessionId);
        $limiter = new RateLimiter();
        $limiter->hit($bucket);

        $cfg = (array) config('limits.validation_fail');
        $count = $limiter->count($bucket, (int) $cfg['window']);

        $wizard = (array) Session::get('wizard', []);
        $wizard['fail_count'] = $count;

        $delays = (array) config('limits.progressive_delay', [1]);
        $delay = (int) ($delays[min(max($count - 1, 0), count($delays) - 1)] ?? 1);
        $wizard['next_allowed_at'] = time() + $delay;

        if ($count >= (int) $cfg['limit']) {
            $wizard['locked_until'] = time() + (int) $cfg['lock_seconds'];
        }

        Session::set('wizard', $wizard);
    }

    public static function clearValidationDelay(): void
    {
        $wizard = (array) Session::get('wizard', []);
        $wizard['next_allowed_at'] = 0;
        $wizard['fail_count'] = 0;
        Session::set('wizard', $wizard);
    }

    /** @return int detik tersisa bila terkunci/ditunda, 0 bila boleh lanjut. */
    public static function validationBlockSeconds(): int
    {
        $wizard = (array) Session::get('wizard', []);
        $locked = (int) ($wizard['locked_until'] ?? 0);
        $delayed = (int) ($wizard['next_allowed_at'] ?? 0);
        $until = max($locked, $delayed);
        return max(0, $until - time());
    }
}
