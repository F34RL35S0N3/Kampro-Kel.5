<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\AuditRepository;
use App\Repositories\LaporanRepository;
use App\Validation\SubmitValidator;
use PDO;
use Throwable;

/**
 * Penyimpanan laporan secara atomik — ASVS V2.3.3 / V2.3.4 (PRD §6.5).
 */
final class ReportService
{
    /**
     * @return array{ok:bool, code:string, reg_no?:string, id?:int, retry?:int}
     */
    public static function submit(array $data, string $idemKey, string $ipHash): array
    {
        $pdo = Database::pdo();
        $year = (int) date('Y');
        $payloadHash = SubmitValidator::payloadHash($data);

        $pdo->beginTransaction();
        try {
            // Serialisasi pengiriman: kunci gate + counter.
            CounterService::acquireGate($pdo);

            // Idempotensi: permintaan yang sama mengembalikan hasil sebelumnya.
            $existing = IdempotencyService::find($pdo, $idemKey);
            if ($existing !== null) {
                $pdo->commit();
                $laporanId = $existing['laporan_id'] !== null ? (int) $existing['laporan_id'] : 0;
                $laporan = $laporanId > 0 ? LaporanRepository::findById($laporanId) : null;
                return [
                    'ok'     => $laporan !== null,
                    'code'   => 'idempotent',
                    'reg_no' => $laporan['reg_no'] ?? '',
                    'id'     => $laporanId,
                ];
            }

            // LIM-01..03 — diperiksa di dalam transaksi agar tidak ada race.
            $limit = LimitService::submissionStatus($ipHash, (string) $data['ketua_nip']);
            if (!$limit['ok']) {
                $pdo->rollBack();
                return ['ok' => false, 'code' => (string) $limit['reason'], 'retry' => (int) $limit['retry']];
            }

            // LIM-09 — laporan identik dalam jendela waktu.
            $window = (int) config('limits.duplicate_window', 2592000);
            if (LaporanRepository::existsPayloadHash($payloadHash, $window)) {
                $pdo->rollBack();
                return ['ok' => false, 'code' => 'duplicate'];
            }

            // Idempotensi: klaim key terlebih dahulu (UNIQUE).
            IdempotencyService::begin($pdo, $idemKey, $payloadHash);

            $sequence = CounterService::next($pdo, 'reg_no:' . $year);
            $regNo = CounterService::formatRegNo($sequence, $year);

            $threshold = (int) config('validation.rincian.dual_approval_threshold', 100000000);
            $requiresDual = (bool) config('app.enable_dual_approval')
                && (int) $data['biaya_rupiah'] >= $threshold;

            $laporanId = LaporanRepository::insertLaporan(
                $pdo,
                $data,
                $regNo,
                $payloadHash,
                $idemKey,
                $ipHash,
                $requiresDual
            );

            LaporanRepository::insertAnggota($pdo, $laporanId, (array) $data['anggota']);
            LaporanRepository::insertLuaran($pdo, $laporanId, (array) $data['luaran']);

            IdempotencyService::attach($pdo, $idemKey, $laporanId);
            LimitService::recordSubmit($pdo, $ipHash);
            AuditRepository::log('LAPORAN_SUBMITTED', $laporanId, null, $ipHash, $pdo);

            $pdo->commit();

            return ['ok' => true, 'code' => 'created', 'reg_no' => $regNo, 'id' => $laporanId];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Bila tabrakan UNIQUE(idem_key) karena request paralel:
            $existing = IdempotencyService::find($pdo, $idemKey);
            if ($existing !== null && $existing['laporan_id'] !== null) {
                $laporan = LaporanRepository::findById((int) $existing['laporan_id']);
                return [
                    'ok'     => $laporan !== null,
                    'code'   => 'idempotent',
                    'reg_no' => $laporan['reg_no'] ?? '',
                    'id'     => (int) $existing['laporan_id'],
                ];
            }

            throw $e;
        }
    }
}
