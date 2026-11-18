<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LaporanRepository
{
    public static function insertLaporan(
        PDO $pdo,
        array $data,
        string $regNo,
        string $payloadHash,
        string $idemKey,
        string $ipHash,
        bool $requiresDualApproval
    ): int {
        $sql = 'INSERT INTO laporan (
                    reg_no, ketua_nama, ketua_jabatan, ketua_nip, ketua_fakultas,
                    ketua_kelompok_keilmuan, ketua_telp, ketua_email,
                    lama_minggu, biaya_rupiah, jenis_penelitian, cpl_prodi, tema_rip,
                    mitra, potensi_adopsi, status, requires_dual_approval,
                    payload_hash, idem_key, ip_hash, created_at, updated_at
                ) VALUES (
                    :reg_no, :ketua_nama, :ketua_jabatan, :ketua_nip, :ketua_fakultas,
                    :ketua_kelompok_keilmuan, :ketua_telp, :ketua_email,
                    :lama_minggu, :biaya_rupiah, :jenis_penelitian, :cpl_prodi, :tema_rip,
                    :mitra, :potensi_adopsi, :status, :requires_dual_approval,
                    :payload_hash, :idem_key, :ip_hash, :created_at, :updated_at
                )';

        $stmt = $pdo->prepare($sql);
        $now = now();
        $stmt->execute([
            ':reg_no'                   => $regNo,
            ':ketua_nama'               => $data['ketua_nama'],
            ':ketua_jabatan'            => $data['ketua_jabatan'],
            ':ketua_nip'                => $data['ketua_nip'],
            ':ketua_fakultas'           => $data['ketua_fakultas'],
            ':ketua_kelompok_keilmuan'  => $data['ketua_kelompok_keilmuan'],
            ':ketua_telp'               => $data['ketua_telp'],
            ':ketua_email'              => $data['ketua_email'],
            ':lama_minggu'              => $data['lama_minggu'],
            ':biaya_rupiah'             => $data['biaya_rupiah'],
            ':jenis_penelitian'         => $data['jenis_penelitian'],
            ':cpl_prodi'                => $data['cpl_prodi'],
            ':tema_rip'                 => $data['tema_rip'],
            ':mitra'                    => $data['mitra'] === '' ? null : $data['mitra'],
            ':potensi_adopsi'           => $data['potensi_adopsi'],
            ':status'                   => 'DIAJUKAN',
            ':requires_dual_approval'   => $requiresDualApproval ? 1 : 0,
            ':payload_hash'             => $payloadHash,
            ':idem_key'                 => $idemKey,
            ':ip_hash'                  => $ipHash,
            ':created_at'               => $now,
            ':updated_at'               => $now,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function insertAnggota(PDO $pdo, int $laporanId, array $rows): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO laporan_anggota (laporan_id, no_urut, nama, nip_npm, bidang_kelas)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($rows as $i => $row) {
            $stmt->execute([
                $laporanId,
                $i + 1,
                $row['nama'],
                $row['nip_npm'],
                $row['bidang_kelas'],
            ]);
        }
    }

    public static function insertLuaran(PDO $pdo, int $laporanId, array $rows): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO laporan_luaran (laporan_id, no_urut, jenis, nama, jumlah)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($rows as $i => $row) {
            $stmt->execute([
                $laporanId,
                $i + 1,
                $row['jenis'],
                $row['nama'] === '' ? null : $row['nama'],
                $row['jumlah'],
            ]);
        }
    }

    public static function findByRegNo(string $regNo): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM laporan WHERE reg_no = ?');
        $stmt->execute([$regNo]);
        $laporan = $stmt->fetch();
        if (!is_array($laporan)) {
            return null;
        }
        return self::hydrate($laporan);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM laporan WHERE id = ?');
        $stmt->execute([$id]);
        $laporan = $stmt->fetch();
        if (!is_array($laporan)) {
            return null;
        }
        return self::hydrate($laporan);
    }

    private static function hydrate(array $laporan): array
    {
        $pdo = Database::pdo();
        $id = (int) $laporan['id'];

        $stmt = $pdo->prepare('SELECT * FROM laporan_anggota WHERE laporan_id = ? ORDER BY no_urut');
        $stmt->execute([$id]);
        $laporan['anggota'] = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM laporan_luaran WHERE laporan_id = ? ORDER BY no_urut');
        $stmt->execute([$id]);
        $laporan['luaran'] = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT a.decision, a.created_at, u.username
             FROM approvals a JOIN admin_users u ON u.id = a.admin_id
             WHERE a.laporan_id = ? ORDER BY a.created_at'
        );
        $stmt->execute([$id]);
        $laporan['approvals'] = $stmt->fetchAll();

        return $laporan;
    }

    /**
     * @return array{rows:array<int,array<string,mixed>>, total:int}
     */
    public static function paginate(string $search = '', string $status = '', int $page = 1, int $perPage = 15): array
    {
        $pdo = Database::pdo();
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(reg_no LIKE ? OR ketua_nama LIKE ? OR ketua_nip LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($status !== '' && in_array($status, ['DIAJUKAN', 'DITINJAU', 'DISETUJUI', 'DITOLAK'], true)) {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM laporan' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT id, reg_no, ketua_nama, ketua_nip, ketua_email, jenis_penelitian,
                       lama_minggu, biaya_rupiah, status, requires_dual_approval, created_at
                FROM laporan' . $whereSql . ' ORDER BY created_at DESC, id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    public static function statusCounts(): array
    {
        $pdo = Database::pdo();
        $counts = ['DIAJUKAN' => 0, 'DITINJAU' => 0, 'DISETUJUI' => 0, 'DITOLAK' => 0];
        $stmt = $pdo->query('SELECT status, COUNT(*) AS total FROM laporan GROUP BY status');
        foreach ($stmt->fetchAll() as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    public static function existsPayloadHash(string $hash, int $windowSeconds): bool
    {
        $pdo = Database::pdo();
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);
        $stmt = $pdo->prepare('SELECT 1 FROM laporan WHERE payload_hash = ? AND created_at >= ? LIMIT 1');
        $stmt->execute([$hash, $since]);
        return $stmt->fetchColumn() !== false;
    }
}
