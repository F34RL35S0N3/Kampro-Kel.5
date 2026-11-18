<?php
/** @var int $step */
/** @var array $errors */
/** @var string $flowToken */
/** @var string $idemKey */
/** @var array $review */
$progressPct = 100;
$ketua = $review['ketua'] ?? [];
$anggota = $review['anggota'] ?? [];
$rincian = $review['rincian'] ?? [];
$luaran = $review['luaran'] ?? [];
$jenisLabel = (array) config('validation.jenis_penelitian', []);
$luaranLabel = (array) config('validation.luaran_types', []);
$dualThreshold = (int) config('validation.rincian.dual_approval_threshold', 100000000);
$needsDual = (bool) config('app.enable_dual_approval') && (int) ($rincian['biaya_rupiah'] ?? 0) >= $dualThreshold;
?>
<div class="split-card">
    <?php require __DIR__ . '/_panel_left.php'; ?>

    <section class="panel-right" id="form-panel" tabindex="-1">
        <header class="form-head">
            <div class="form-head__bar">
                <span class="step-pill">Langkah <?= $step ?> dari 5</span>
                <button type="button" class="icon-close" aria-label="Batalkan dan kembali" data-dialog-open="#cancel-dialog">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <h2><?= e(step_title($step)) ?></h2>
            <p class="form-subtitle"><?= e(step_subtitle($step)) ?></p>
            <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="100" aria-label="Kemajuan pengisian">
                <span style="width: 100%"></span>
            </div>
        </header>

        <form class="wizard-form" method="post" action="<?= url('/kirim') ?>" novalidate data-wizard-form data-step="5" data-min-seconds="5">
            <?= csrf_field() ?>
            <input type="hidden" name="_flow" value="<?= e($flowToken) ?>">
            <input type="hidden" name="idem_key" value="<?= e($idemKey) ?>">
            <div class="hp-field" aria-hidden="true">
                <label for="hp_website">Website</label>
                <input type="text" id="hp_website" name="_hp_website" tabindex="-1" autocomplete="off">
            </div>

            <?php if (isset($errors['_form'])): ?>
                <div class="alert alert--error" role="alert"><strong>Periksa kembali.</strong><span><?= e($errors['_form']) ?></span></div>
            <?php endif; ?>

            <div class="review">
                <section class="review-card" aria-labelledby="review-ketua">
                    <div class="review-card__head">
                        <div>
                            <span class="review-card__eyebrow">Tim Pelaksana</span>
                            <h3 id="review-ketua">Ketua Tim</h3>
                        </div>
                        <a class="btn btn--tiny" href="<?= url('/langkah/1') ?>">Ubah</a>
                    </div>
                    <dl class="review-grid">
                        <div><dt>Nama Lengkap</dt><dd><?= e($ketua['ketua_nama'] ?? '—') ?></dd></div>
                        <div><dt>Jabatan</dt><dd><?= e($ketua['ketua_jabatan'] ?? '—') ?></dd></div>
                        <div><dt>NIP</dt><dd><?= e($ketua['ketua_nip'] ?? '—') ?></dd></div>
                        <div><dt>Fakultas/Sekolah</dt><dd><?= e($ketua['ketua_fakultas'] ?? '—') ?></dd></div>
                        <div><dt>Kelompok Keilmuan</dt><dd><?= e($ketua['ketua_kelompok_keilmuan'] ?? '—') ?></dd></div>
                        <div><dt>Telp/HP</dt><dd><?= e($ketua['ketua_telp'] ?? '—') ?></dd></div>
                        <div class="review-grid__wide"><dt>E-mail</dt><dd><?= e($ketua['ketua_email'] ?? '—') ?></dd></div>
                    </dl>
                </section>

                <section class="review-card" aria-labelledby="review-anggota">
                    <div class="review-card__head">
                        <div>
                            <span class="review-card__eyebrow">Tim Pelaksana</span>
                            <h3 id="review-anggota">Anggota Tim (<?= count($anggota) ?>)</h3>
                        </div>
                        <a class="btn btn--tiny" href="<?= url('/langkah/2') ?>">Ubah</a>
                    </div>
                    <div class="table-wrap">
                        <table class="review-table">
                            <caption class="sr-only">Daftar anggota tim</caption>
                            <thead><tr><th scope="col">No</th><th scope="col">Nama &amp; Gelar</th><th scope="col">NIP/NPM</th><th scope="col">Bidang/Kelas</th></tr></thead>
                            <tbody>
                            <?php foreach ($anggota as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= e($row['nama'] ?? '') ?></td>
                                    <td><?= e($row['nip_npm'] ?? '') ?></td>
                                    <td><?= e($row['bidang_kelas'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="review-card" aria-labelledby="review-rincian">
                    <div class="review-card__head">
                        <div>
                            <span class="review-card__eyebrow">Rincian Penelitian</span>
                            <h3 id="review-rincian">Rencana &amp; Konteks</h3>
                        </div>
                        <a class="btn btn--tiny" href="<?= url('/langkah/3') ?>">Ubah</a>
                    </div>
                    <dl class="review-grid">
                        <div><dt>Lama Penelitian</dt><dd><?= e((string) ($rincian['lama_minggu'] ?? '—')) ?> minggu</dd></div>
                        <div><dt>Biaya Penelitian</dt><dd><?= e(rupiah((int) ($rincian['biaya_rupiah'] ?? 0))) ?></dd></div>
                        <div><dt>Jenis Penelitian</dt><dd><?= e($jenisLabel[$rincian['jenis_penelitian'] ?? ''] ?? '—') ?></dd></div>
                        <div><dt>Tema RIP</dt><dd><?= e($rincian['tema_rip'] ?? '—') ?></dd></div>
                        <div class="review-grid__wide"><dt>Mitra Penelitian</dt><dd><?= e(($rincian['mitra'] ?? '') !== '' ? $rincian['mitra'] : 'Tidak ada') ?></dd></div>
                        <div class="review-grid__wide"><dt>CPL Program Studi</dt><dd class="prewrap"><?= e($rincian['cpl_prodi'] ?? '—') ?></dd></div>
                        <div class="review-grid__wide"><dt>Potensi Adopsi</dt><dd class="prewrap"><?= e($rincian['potensi_adopsi'] ?? '—') ?></dd></div>
                    </dl>
                </section>

                <section class="review-card" aria-labelledby="review-luaran">
                    <div class="review-card__head">
                        <div>
                            <span class="review-card__eyebrow">Target Output</span>
                            <h3 id="review-luaran">Luaran (<?= count($luaran) ?>)</h3>
                        </div>
                        <a class="btn btn--tiny" href="<?= url('/langkah/4') ?>">Ubah</a>
                    </div>
                    <div class="table-wrap">
                        <table class="review-table">
                            <caption class="sr-only">Daftar target luaran</caption>
                            <thead><tr><th scope="col">No</th><th scope="col">Jenis</th><th scope="col">Nama/Keterangan</th><th scope="col">Jumlah</th></tr></thead>
                            <tbody>
                            <?php foreach ($luaran as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= e($luaranLabel[$row['jenis'] ?? ''] ?? ($row['jenis'] ?? '')) ?></td>
                                    <td><?= e(($row['nama'] ?? '') !== '' ? $row['nama'] : '—') ?></td>
                                    <td><?= e((string) ($row['jumlah'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <?php if ($needsDual): ?>
                <div class="alert alert--info" role="note">
                    <strong>Perlu persetujuan ganda.</strong>
                    <span>Laporan dengan biaya ≥ <?= e(rupiah($dualThreshold)) ?> akan ditinjau oleh dua admin berbeda sebelum disetujui.</span>
                </div>
            <?php endif; ?>

            <div class="consent">
                <label class="checkbox">
                    <input type="checkbox" name="agree" value="1" required>
                    <span>
                        Saya menyatakan bahwa seluruh data yang diisikan <strong>benar, lengkap, dan dapat dipertanggungjawabkan</strong>.
                        Saya memahami laporan yang telah dikirim akan ditinjau oleh LPPM.
                    </span>
                </label>
            </div>

            <div class="form-actions">
                <div class="actions-buttons">
                    <a class="btn btn--ghost" href="<?= url('/langkah/4') ?>">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
                        Kembali
                    </a>
                    <button class="btn btn--primary btn--submit" type="submit">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                        Kirim Laporan
                    </button>
                </div>
                <div class="help-card">
                    <span class="help-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 21l2.2-5.2A8.5 8.5 0 1 1 21 11.5Z"/></svg>
                    </span>
                    <div>
                        <strong>Butuh bantuan?</strong>
                        <a href="https://wa.me/<?= e(preg_replace('/\D/', '', (string) config('app.lppm_whatsapp'))) ?>" target="_blank" rel="noopener">Hubungi LPPM via WhatsApp</a>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>
<?php require __DIR__ . '/_cancel_dialog.php'; ?>
