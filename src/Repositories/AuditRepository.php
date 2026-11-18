<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class AuditRepository
{
    public static function log(
        string $event,
        ?int $laporanId = null,
        ?string $actor = null,
        ?string $ipHash = null,
        ?PDO $pdo = null
    ): void {
        try {
            $pdo = $pdo ?? Database::pdo();
            $stmt = $pdo->prepare(
                'INSERT INTO audit_log (event, laporan_id, actor, ip_hash, created_at)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$event, $laporanId, $actor, $ipHash, now()]);
        } catch (\Throwable) {
            // Audit tidak boleh menggagalkan transaksi utama; log file sebagai cadangan.
            \App\Support\Logger::event('AUDIT_FALLBACK', ['event' => $event, 'laporan_id' => $laporanId]);
        }
    }
}
