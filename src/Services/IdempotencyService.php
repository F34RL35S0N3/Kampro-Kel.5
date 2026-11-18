<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Idempotensi pengiriman (V2.3.4): UNIQUE(idem_key) mencegah duplikasi
 * akibat double-click / replay.
 */
final class IdempotencyService
{
    public static function find(PDO $pdo, string $key): ?array
    {
        $stmt = $pdo->prepare('SELECT idem_key, laporan_id, payload_hash FROM idempotency WHERE idem_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function begin(PDO $pdo, string $key, string $payloadHash): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO idempotency (idem_key, laporan_id, payload_hash, created_at) VALUES (?, NULL, ?, ?)'
        );
        $stmt->execute([$key, $payloadHash, now()]);
    }

    public static function attach(PDO $pdo, string $key, int $laporanId): void
    {
        $stmt = $pdo->prepare('UPDATE idempotency SET laporan_id = ? WHERE idem_key = ?');
        $stmt->execute([$laporanId, $key]);
    }
}
