<?php
/** @var array $admin */
/** @var array $laporan */
/** @var string|null $flash */
$jenisLabel = (array) config('validation.jenis_penelitian', []);
$luaranLabel = (array) config('validation.luaran_types', []);
$statusLabels = ['DIAJUKAN' => 'Diajukan', 'DITINJAU' => 'Ditinjau', 'DISETUJUI' => 'Disetujui', 'DITOLAK' => 'Ditolak'];
$status = (string) $laporan['status'];
$isFinal = in_array($status, ['DISETUJUI', 'DITOLAK'], true);
$approvals = $laporan['approvals'] ?? [];
$setuju = count(array_filter($approvals, static fn ($a) => ($a['decision'] ?? '') === 'SETUJU'));
$requiresDual = (int) $laporan['requires_dual_approval'] === 1;
?>
<div class="detail-head">
    <div>
        <a class="back-link" href="<?= url('/admin/laporan') ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
            Kembali ke daftar
        </a>
        <h2 class="detail-title"><?= e($laporan['reg_no']) ?></h2>
        <div class="detail-meta">
            <span class="status-badge status--<?= e(strtolower($status)) ?>"><?= e($statusLabels[$status] ?? $status) ?></span>
            <span>Dikirim <?= e(date('d M Y, H:i', strtotime((string) $laporan['created_at']))) ?> WIB</span>
            <?php if ($requiresDual): ?><span class="tag tag--dual">Persetujuan ganda</span><?php endif; ?>
        </div>
    </div>
    <div class="detail-actions">
        <?php if (!$isFinal): ?>
            <form method="post" action="<?= url('/admin/laporan/' . (int) $laporan['id'] . '/setujui') ?>">
                <?= csrf_field() ?>
                <button class="btn btn--success" type="submit">Setujui</button>
            </form>
            <form method="post" action="<?= url('/admin/laporan/' . (int) $laporan['id'] . '/tolak') ?>">
                <?= csrf_field() ?>
                <button class="btn btn--danger" type="submit">Tolak</button>
            </form>
        <?php else: ?>
            <span class="final-badge">Laporan sudah final</span>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($flash)): ?>
    <div class="alert alert--success" role="status"><strong>Berhasil.</strong><span><?= e($flash) ?></span></div>
<?php endif; ?>

<?php if ($requiresDual && !$isFinal): ?>
    <div class="alert alert--info" role="note">
        <strong>Persetujuan ganda diperlukan.</strong>
        <span>Laporan ini membutuhkan dua admin berbeda. Saat ini tercatat <strong><?= $setuju ?></strong> dari 2 persetujuan.</span>
    </div>
<?php endif; ?>

<div class="detail-grid">
    <section class="panel">
        <h3 class="section-title">Data Ketua Tim</h3>
        <dl class="review-grid">
            <div><dt>Nama Lengkap</dt><dd><?= e($laporan['ketua_nama']) ?></dd></div>
            <div><dt>Jabatan</dt><dd><?= e($laporan['ketua_jabatan']) ?></dd></div>
            <div><dt>NIP</dt><dd><?= e($laporan['ketua_nip']) ?></dd></div>
            <div><dt>Fakultas/Sekolah</dt><dd><?= e($laporan['ketua_fakultas']) ?></dd></div>
            <div><dt>Kelompok Keilmuan</dt><dd><?= e($laporan['ketua_kelompok_keilmuan']) ?></dd></div>
            <div><dt>Telp/HP</dt><dd><?= e($laporan['ketua_telp']) ?></dd></div>
            <div class="review-grid__wide"><dt>E-mail</dt><dd><a href="mailto:<?= e($laporan['ketua_email']) ?>"><?= e($laporan['ketua_email']) ?></a></dd></div>
        </dl>
    </section>

    <section class="panel">
        <h3 class="section-title">Rincian Penelitian</h3>
        <dl class="review-grid">
            <div><dt>Lama Penelitian</dt><dd><?= e((string) $laporan['lama_minggu']) ?> minggu</dd></div>
            <div><dt>Biaya</dt><dd><?= e(rupiah((int) $laporan['biaya_rupiah'])) ?></dd></div>
            <div><dt>Jenis Penelitian</dt><dd><?= e($jenisLabel[$laporan['jenis_penelitian']] ?? $laporan['jenis_penelitian']) ?></dd></div>
            <div><dt>Tema RIP</dt><dd><?= e($laporan['tema_rip']) ?></dd></div>
            <div class="review-grid__wide"><dt>Mitra</dt><dd><?= e(($laporan['mitra'] ?? '') !== '' ? $laporan['mitra'] : 'Tidak ada') ?></dd></div>
            <div class="review-grid__wide"><dt>CPL Program Studi</dt><dd class="prewrap"><?= e($laporan['cpl_prodi']) ?></dd></div>
            <div class="review-grid__wide"><dt>Potensi Adopsi</dt><dd class="prewrap"><?= e($laporan['potensi_adopsi']) ?></dd></div>
        </dl>
    </section>
</div>

<section class="panel">
    <h3 class="section-title">Anggota Tim (<?= count($laporan['anggota']) ?>)</h3>
    <div class="table-wrap">
        <table class="data-table">
            <caption class="sr-only">Anggota tim</caption>
            <thead><tr><th scope="col">No</th><th scope="col">Nama &amp; Gelar</th><th scope="col">NIP/NPM</th><th scope="col">Bidang/Kelas</th></tr></thead>
            <tbody>
            <?php foreach ($laporan['anggota'] as $row): ?>
                <tr>
                    <td><?= (int) $row['no_urut'] ?></td>
                    <td><?= e($row['nama']) ?></td>
                    <td><?= e($row['nip_npm']) ?></td>
                    <td><?= e($row['bidang_kelas']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h3 class="section-title">Target Luaran (<?= count($laporan['luaran']) ?>)</h3>
    <div class="table-wrap">
        <table class="data-table">
            <caption class="sr-only">Target luaran</caption>
            <thead><tr><th scope="col">No</th><th scope="col">Jenis</th><th scope="col">Nama/Keterangan</th><th scope="col">Jumlah</th></tr></thead>
            <tbody>
            <?php foreach ($laporan['luaran'] as $row): ?>
                <tr>
                    <td><?= (int) $row['no_urut'] ?></td>
                    <td><?= e($luaranLabel[$row['jenis']] ?? $row['jenis']) ?></td>
                    <td><?= e(($row['nama'] ?? '') !== '' ? $row['nama'] : '—') ?></td>
                    <td><?= (int) $row['jumlah'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h3 class="section-title">Riwayat Keputusan</h3>
    <?php if ($approvals === []): ?>
        <p class="muted">Belum ada keputusan dari admin.</p>
    <?php else: ?>
        <ol class="timeline">
            <?php foreach ($approvals as $a): ?>
                <li class="timeline__item timeline__item--<?= e(strtolower((string) $a['decision'])) ?>">
                    <span class="timeline__dot" aria-hidden="true"></span>
                    <div>
                        <strong><?= e($a['decision'] === 'SETUJU' ? 'Disetujui' : 'Ditolak') ?></strong> oleh <?= e($a['username']) ?>
                        <small><?= e(date('d M Y, H:i', strtotime((string) $a['created_at']))) ?> WIB</small>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>
