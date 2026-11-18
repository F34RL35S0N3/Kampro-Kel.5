<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Rate limiting sliding-window berbasis database (AA-01 / V2.4.1).
 * Tabel rate_limits dibersihkan berkala (cron > 24 jam).
 */
final class RateLimiter
{
    public function hit(string $bucket): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO rate_limits (bucket, created_at) VALUES (?, ?)');
        $stmt->execute([$bucket, now()]);
    }

    public function count(string $bucket, int $windowSeconds): int
    {
        $pdo = Database::pdo();
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND created_at >= ?');
        $stmt->execute([$bucket, $since]);
        return (int) $stmt->fetchColumn();
    }

    /** Detik sampai bucket kembali di bawah batas. */
    public function retryAfter(string $bucket, int $windowSeconds): int
    {
        $pdo = Database::pdo();
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);
        $stmt = $pdo->prepare(
            'SELECT MIN(created_at) FROM rate_limits WHERE bucket = ? AND created_at >= ?'
        );
        $stmt->execute([$bucket, $since]);
        $oldest = $stmt->fetchColumn();
        if (!$oldest) {
            return 0;
        }
        $elapsed = time() - strtotime((string) $oldest);
        return max(0, $windowSeconds - $elapsed);
    }

    public function purge(int $olderThanSeconds = 86400): void
    {
        $pdo = Database::pdo();
        $cutoff = date('Y-m-d H:i:s', time() - $olderThanSeconds);
        $stmt = $pdo->prepare('DELETE FROM rate_limits WHERE created_at < ?');
        $stmt->execute([$cutoff]);
    }

    public static function globalPdo(): PDO
    {
        return Database::pdo();
    }
}
