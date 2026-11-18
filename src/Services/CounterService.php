<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Nomor registrasi berurutan dengan penguncian baris (V2.3.4).
 * Selalu dipanggil di dalam transaksi.
 */
final class CounterService
{
    /** Kunci "gate" agar perhitungan kuota + nomor registrasi serial. */
    public static function acquireGate(PDO $pdo): void
    {
        if (Database::isSqlite()) {
            return;
        }
        $pdo->exec("INSERT INTO counters (name, value) SELECT 'submit_gate', 0
                    WHERE NOT EXISTS (SELECT 1 FROM counters WHERE name = 'submit_gate')");
        $stmt = $pdo->query("SELECT value FROM counters WHERE name = 'submit_gate' FOR UPDATE");
        $stmt->fetchColumn();
    }

    public static function next(PDO $pdo, string $name): int
    {
        $forUpdate = Database::isSqlite() ? '' : ' FOR UPDATE';
        $stmt = $pdo->prepare('SELECT value FROM counters WHERE name = ?' . $forUpdate);
        $stmt->execute([$name]);
        $value = $stmt->fetchColumn();

        if ($value === false) {
            $insert = $pdo->prepare('INSERT INTO counters (name, value) VALUES (?, 1)');
            $insert->execute([$name]);
            return 1;
        }

        $next = ((int) $value) + 1;
        $update = $pdo->prepare('UPDATE counters SET value = ? WHERE name = ?');
        $update->execute([$next, $name]);
        return $next;
    }

    public static function formatRegNo(int $sequence, ?int $year = null): string
    {
        return sprintf('LRP-%d-%06d', $year ?? (int) date('Y'), $sequence);
    }
}
