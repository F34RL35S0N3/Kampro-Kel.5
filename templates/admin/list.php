<?php
/** @var array $admin */
/** @var array $rows */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var string $search */
/** @var string $status */
/** @var array $counts */
/** @var string|null $flash */
$statusLabels = [
    'DIAJUKAN'  => 'Diajukan',
    'DITINJAU'  => 'Ditinjau',
    'DISETUJUI' => 'Disetujui',
    'DITOLAK'   => 'Ditolak',
];
$statusClass = static fn (string $s): string => 'status--' . strtolower($s);
$queryBase = static function (array $overrides) use ($search, $status, $page): string {
    $params = array_merge(['q' => $search, 'status' => $status, 'page' => $page], $overrides);
    $params = array_filter($params, static fn ($v) => $v !== '' && $v !== null);
    return url('/admin/laporan' . ($params ? '?' . http_build_query($params) : ''));
};
?>
<?php if (!empty($flash)): ?>
    <div class="alert alert--success" role="status"><strong>Berhasil.</strong><span><?= e($flash) ?></span></div>
<?php endif; ?>

<section class="stat-grid" aria-label="Ringkasan status laporan">
    <article class="stat-card">
        <span class="stat-card__label">Total laporan</span>
        <strong class="stat-card__value"><?= (int) $total ?></strong>
        <span class="stat-card__hint">seluruh data tersimpan</span>
    </article>
    <article class="stat-card stat-card--amber">
        <span class="stat-card__label">Diajukan</span>
        <strong class="stat-card__value"><?= (int) ($counts['DIAJUKAN'] ?? 0) ?></strong>
        <span class="stat-card__hint">menunggu tinjauan</span>
    </article>
    <article class="stat-card stat-card--purple">
        <span class="stat-card__label">Ditinjau</span>
        <strong class="stat-card__value"><?= (int) ($counts['DITINJAU'] ?? 0) ?></strong>
        <span class="stat-card__hint">persetujuan sebagian</span>
    </article>
    <article class="stat-card stat-card--green">
        <span class="stat-card__label">Disetujui</span>
        <strong class="stat-card__value"><?= (int) ($counts['DISETUJUI'] ?? 0) ?></strong>
        <span class="stat-card__hint">laporan final</span>
    </article>
</section>

<section class="panel">
    <form class="filter-bar" method="get" action="<?= url('/admin/laporan') ?>" role="search">
        <div class="filter-bar__search">
            <label class="sr-only" for="q">Cari laporan</label>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/></svg>
            <input id="q" name="q" type="search" value="<?= e($search) ?>" placeholder="Cari nomor registrasi, nama ketua, atau NIP…" maxlength="100">
        </div>
        <div class="filter-bar__select">
            <label class="sr-only" for="status">Filter status</label>
            <select id="status" name="status">
                <option value="">Semua status</option>
                <?php foreach ($statusLabels as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn--primary" type="submit">Terapkan</button>
        <?php if ($search !== '' || $status !== ''): ?>
            <a class="btn btn--ghost" href="<?= url('/admin/laporan') ?>">Reset</a>
        <?php endif; ?>
    </form>

    <?php if ($rows === []): ?>
        <div class="empty-state">
            <div class="empty-state__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg>
            </div>
            <h2>Belum ada laporan</h2>
            <p>Laporan yang dikirim melalui formulir publik akan muncul di sini.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <caption class="sr-only">Daftar laporan penelitian</caption>
                <thead>
                    <tr>
                        <th scope="col">No. Registrasi</th>
                        <th scope="col">Ketua Tim</th>
                        <th scope="col">Jenis</th>
                        <th scope="col">Biaya</th>
                        <th scope="col">Status</th>
                        <th scope="col">Dikirim</th>
                        <th scope="col"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td data-label="No. Registrasi">
                                <a class="mono-link" href="<?= url('/admin/laporan/' . (int) $row['id']) ?>"><?= e($row['reg_no']) ?></a>
                                <?php if ((int) ($row['requires_dual_approval'] ?? 0) === 1): ?>
                                    <span class="tag tag--dual">Persetujuan ganda</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Ketua Tim">
                                <strong><?= e($row['ketua_nama']) ?></strong>
                                <small><?= e($row['ketua_nip']) ?></small>
                            </td>
                            <td data-label="Jenis"><?= e(ucfirst((string) $row['jenis_penelitian'])) ?></td>
                            <td data-label="Biaya"><?= e(rupiah((int) $row['biaya_rupiah'])) ?></td>
                            <td data-label="Status">
                                <span class="status-badge <?= e($statusClass((string) $row['status'])) ?>"><?= e($statusLabels[$row['status']] ?? $row['status']) ?></span>
                            </td>
                            <td data-label="Dikirim">
                                <span class="nowrap"><?= e(date('d M Y', strtotime((string) $row['created_at']))) ?></span>
                                <small><?= e(date('H:i', strtotime((string) $row['created_at']))) ?> WIB</small>
                            </td>
                            <td class="table-actions">
                                <a class="btn btn--tiny" href="<?= url('/admin/laporan/' . (int) $row['id']) ?>">Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="pagination" aria-label="Navigasi halaman">
            <span class="pagination__info">
                Menampilkan <?= count($rows) ?> dari <?= (int) $total ?> laporan
            </span>
            <div class="pagination__controls">
                <?php if ($page > 1): ?>
                    <a class="btn btn--tiny" href="<?= e($queryBase(['page' => $page - 1])) ?>" rel="prev">Sebelumnya</a>
                <?php else: ?>
                    <span class="btn btn--tiny is-disabled" aria-disabled="true">Sebelumnya</span>
                <?php endif; ?>
                <span class="pagination__page">Halaman <?= (int) $page ?> / <?= (int) $totalPages ?></span>
                <?php if ($page < $totalPages): ?>
                    <a class="btn btn--tiny" href="<?= e($queryBase(['page' => $page + 1])) ?>" rel="next">Berikutnya</a>
                <?php else: ?>
                    <span class="btn btn--tiny is-disabled" aria-disabled="true">Berikutnya</span>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
</section>
