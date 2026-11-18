<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\AuditRepository;
use PDO;
use Throwable;

/**
 * Persetujuan multi-pengguna — ASVS V2.3.5 (Level 3, PRD §6.5).
 */
final class ApprovalService
{
    /**
     * @return array{ok:bool, code:string, status?:string}
     */
    public static function decide(int $laporanId, int $adminId, string $decision, string $actor, ?string $ipHash = null): array
    {
        if (!in_array($decision, ['SETUJU', 'TOLAK'], true)) {
            return ['ok' => false, 'code' => 'invalid_decision'];
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $lock = Database::isSqlite() ? '' : ' FOR UPDATE';
            $stmt = $pdo->prepare('SELECT * FROM laporan WHERE id = ?' . $lock);
            $stmt->execute([$laporanId]);
            $laporan = $stmt->fetch();
            if (!is_array($laporan)) {
                $pdo->rollBack();
                return ['ok' => false, 'code' => 'not_found'];
            }
            if ($laporan['status'] === 'DITOLAK') {
                $pdo->rollBack();
                return ['ok' => false, 'code' => 'already_rejected', 'status' => 'DITOLAK'];
            }
            if ($laporan['status'] === 'DISETUJUI') {
                $pdo->rollBack();
                return ['ok' => false, 'code' => 'already_approved', 'status' => 'DISETUJUI'];
            }

            // UNIQUE(laporan_id, admin_id) — satu admin hanya satu suara.
            $insert = $pdo->prepare(
                'INSERT INTO approvals (laporan_id, admin_id, decision, created_at) VALUES (?, ?, ?, ?)'
            );
            try {
                $insert->execute([$laporanId, $adminId, $decision, now()]);
            } catch (Throwable $e) {
                $pdo->rollBack();
                if (self::isUniqueViolation($e)) {
                    return ['ok' => false, 'code' => 'already_voted', 'status' => (string) $laporan['status']];
                }
                throw $e;
            }

            if ($decision === 'TOLAK') {
                $status = 'DITOLAK';
            } elseif ((int) $laporan['requires_dual_approval'] === 1) {
                $count = $pdo->prepare("SELECT COUNT(DISTINCT admin_id) FROM approvals WHERE laporan_id = ? AND decision = 'SETUJU'");
                $count->execute([$laporanId]);
                $approvals = (int) $count->fetchColumn();
                $status = $approvals >= 2 ? 'DISETUJUI' : 'DITINJAU';
            } else {
                $status = 'DISETUJUI';
            }

            $update = $pdo->prepare('UPDATE laporan SET status = ?, updated_at = ? WHERE id = ?');
            $update->execute([$status, now(), $laporanId]);

            AuditRepository::log(
                $decision === 'SETUJU' ? 'APPROVAL_SETUJU' : 'APPROVAL_TOLAK',
                $laporanId,
                $actor,
                $ipHash,
                $pdo
            );

            $pdo->commit();
            return ['ok' => true, 'code' => 'updated', 'status' => $status];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function isUniqueViolation(Throwable $e): bool
    {
        if ($e instanceof \PDOException) {
            return in_array($e->getCode(), ['23000', '23505'], true)
                || str_contains(strtolower($e->getMessage()), 'unique');
        }
        return false;
    }
}
