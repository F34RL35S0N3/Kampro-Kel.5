<?php
/** @var array $laporan */
$ketua = $laporan;
$anggota = $laporan['anggota'] ?? [];
$luaran = $laporan['luaran'] ?? [];
$jenisLabel = (array) config('validation.jenis_penelitian', []);
$luaranLabel = (array) config('validation.luaran_types', []);
$statusLabel = [
    'DIAJUKAN'   => 'Diajukan',
    'DITINJAU'   => 'Ditinjau',
    'DISETUJUI'  => 'Disetujui',
    'DITOLAK'    => 'Ditolak',
][$laporan['status'] ?? 'DIAJUKAN'] ?? 'Diajukan';
?>
<div class="success-wrap">
    <div class="success-hero">
        <div class="success-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        <p class="eyebrow eyebrow--dark">Laporan terkirim</p>
        <h1>Terima kasih, laporan Anda sudah kami terima.</h1>
        <p class="success-lead">
            Simpan nomor registrasi berikut. Nomor ini diperlukan saat menghubungi LPPM
            atau menindaklanjuti proses tinjauan.
        </p>
        <div class="reg-no" aria-label="Nomor registrasi">
            <span><?= e($laporan['reg_no'] ?? '') ?></span>
            <button type="button" class="btn btn--tiny" data-copy="<?= e($laporan['reg_no'] ?? '') ?>">Salin</button>
        </div>
        <div class="success-meta">
            <span class="status-badge status--<?= e(strtolower((string) ($laporan['status'] ?? 'DIAJUKAN'))) ?>"><?= e($statusLabel) ?></span>
            <span>Dikirim <?= e(date('d M Y, H:i', strtotime((string) ($laporan['created_at'] ?? 'now')))) ?> WIB</span>
        </div>
    </div>

    <div class="success-grid">
        <section class="summary-card">
            <h2>Ringkasan laporan</h2>
            <dl class="review-grid">
                <div><dt>Ketua Tim</dt><dd><?= e($ketua['ketua_nama'] ?? '—') ?></dd></div>
                <div><dt>NIP</dt><dd><?= e($ketua['ketua_nip'] ?? '—') ?></dd></div>
                <div><dt>Fakultas/Sekolah</dt><dd><?= e($ketua['ketua_fakultas'] ?? '—') ?></dd></div>
                <div><dt>E-mail</dt><dd><?= e($ketua['ketua_email'] ?? '—') ?></dd></div>
                <div><dt>Lama Penelitian</dt><dd><?= e((string) ($ketua['lama_minggu'] ?? '—')) ?> minggu</dd></div>
                <div><dt>Biaya</dt><dd><?= e(rupiah((int) ($ketua['biaya_rupiah'] ?? 0))) ?></dd></div>
                <div><dt>Jenis Penelitian</dt><dd><?= e($jenisLabel[$ketua['jenis_penelitian'] ?? ''] ?? '—') ?></dd></div>
                <div><dt>Tema RIP</dt><dd><?= e($ketua['tema_rip'] ?? '—') ?></dd></div>
                <div class="review-grid__wide"><dt>Mitra</dt><dd><?= e(($ketua['mitra'] ?? '') !== '' ? $ketua['mitra'] : 'Tidak ada') ?></dd></div>
            </dl>
        </section>

        <section class="summary-card">
            <h2>Anggota &amp; luaran</h2>
            <h3 class="mini-heading">Anggota (<?= count($anggota) ?>)</h3>
            <ul class="pill-list">
                <?php foreach ($anggota as $row): ?>
                    <li><strong><?= e($row['nama'] ?? '') ?></strong><span><?= e($row['nip_npm'] ?? '') ?></span></li>
                <?php endforeach; ?>
            </ul>
            <h3 class="mini-heading">Target luaran (<?= count($luaran) ?>)</h3>
            <ul class="pill-list">
                <?php foreach ($luaran as $row): ?>
                    <li><strong><?= e($luaranLabel[$row['jenis'] ?? ''] ?? ($row['jenis'] ?? '')) ?></strong><span><?= e((string) ($row['jumlah'] ?? '')) ?>×</span></li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

    <div class="success-actions">
        <button type="button" class="btn btn--ghost" data-print>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
            Cetak ringkasan
        </button>
        <a class="btn btn--primary" href="<?= url('/') ?>">Buat laporan baru</a>
    </div>

    <p class="success-note">
        Laporan Anda akan ditinjau oleh admin LPPM. Perubahan status dapat ditanyakan
        melalui <a href="mailto:<?= e((string) config('app.lppm_email')) ?>"><?= e((string) config('app.lppm_email')) ?></a>.
    </p>
</div>
