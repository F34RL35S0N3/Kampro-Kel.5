<?php
/** @var int $step */
/** @var array $form */
/** @var array $errors */
/** @var string $flowToken */
$progressPct = (int) round(($step / 5) * 100);
$rows = $form['anggota'] ?? [];
$rows = array_values(array_map(static fn ($r) => is_array($r) ? $r : ['nama' => '', 'nip_npm' => '', 'bidang_kelas' => ''], $rows));
if ($rows === []) {
    $rows = [['nama' => '', 'nip_npm' => '', 'bidang_kelas' => '']];
}
$rowErrors = isset($errors['anggota']) && is_array($errors['anggota']) ? $errors['anggota'] : [];
$listError = isset($errors['anggota']) && is_string($errors['anggota']) ? $errors['anggota'] : null;
$err = static fn (int $i, string $key): ?string => isset($rowErrors[$i][$key]) ? (string) $rowErrors[$i][$key] : null;
$nextRows = min(10, count($rows) + 1);
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
            <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $progressPct ?>" aria-label="Kemajuan pengisian">
                <span style="width: <?= $progressPct ?>%"></span>
            </div>
        </header>

        <form class="wizard-form" method="post" action="<?= url('/langkah/' . $step) ?>" novalidate data-wizard-form data-step="<?= $step ?>" data-min-seconds="5">
            <?= csrf_field() ?>
            <input type="hidden" name="_flow" value="<?= e($flowToken) ?>">
            <div class="hp-field" aria-hidden="true">
                <label for="hp_website">Website</label>
                <input type="text" id="hp_website" name="_hp_website" tabindex="-1" autocomplete="off">
            </div>

            <?php if (isset($errors['_form'])): ?>
                <div class="alert alert--error" role="alert"><strong>Terjadi kesalahan.</strong><span><?= e($errors['_form']) ?></span></div>
            <?php endif; ?>
            <?php if ($listError !== null): ?>
                <div class="alert alert--error" role="alert"><strong>Periksa data anggota.</strong><span><?= e($listError) ?></span></div>
            <?php endif; ?>

            <div class="dynamic-table" data-dynamic="anggota" data-max="10">
                <div class="dynamic-head" aria-hidden="true">
                    <span>No</span><span>Nama &amp; Gelar Akademik</span><span>NIP / NPM</span><span>Bidang Ilmu / Kelas</span><span></span>
                </div>

                <div class="dynamic-body" data-rows>
                    <?php foreach ($rows as $i => $row): ?>
                        <div class="dynamic-row" data-row>
                            <span class="row-index" data-row-index><?= (int) $i + 1 ?></span>
                            <div class="dynamic-row__field">
                                <label class="mini-label" for="anggota_<?= (int) $i ?>_nama">Nama &amp; Gelar</label>
                                <input id="anggota_<?= (int) $i ?>_nama" name="anggota[<?= (int) $i ?>][nama]" type="text"
                                       value="<?= e($row['nama'] ?? '') ?>" placeholder="cth. Budi Santoso, S.Kom."
                                       minlength="3" maxlength="100" required
                                       aria-invalid="<?= $err((int) $i, 'nama') !== null ? 'true' : 'false' ?>"
                                       <?= $err((int) $i, 'nama') !== null ? 'aria-describedby="err-anggota-' . (int) $i . '-nama"' : '' ?>>
                                <?php if ($err((int) $i, 'nama') !== null): ?><p class="field-error" id="err-anggota-<?= (int) $i ?>-nama" role="alert"><?= e($err((int) $i, 'nama')) ?></p><?php endif; ?>
                            </div>
                            <div class="dynamic-row__field">
                                <label class="mini-label" for="anggota_<?= (int) $i ?>_nip">NIP / NPM</label>
                                <input id="anggota_<?= (int) $i ?>_nip" name="anggota[<?= (int) $i ?>][nip_npm]" type="text"
                                       value="<?= e($row['nip_npm'] ?? '') ?>" placeholder="NIP 18 digit / NPM 8–15 digit"
                                       inputmode="numeric" maxlength="18" required data-digits
                                       aria-invalid="<?= $err((int) $i, 'nip_npm') !== null ? 'true' : 'false' ?>"
                                       <?= $err((int) $i, 'nip_npm') !== null ? 'aria-describedby="err-anggota-' . (int) $i . '-nip"' : '' ?>>
                                <?php if ($err((int) $i, 'nip_npm') !== null): ?><p class="field-error" id="err-anggota-<?= (int) $i ?>-nip" role="alert"><?= e($err((int) $i, 'nip_npm')) ?></p><?php endif; ?>
                            </div>
                            <div class="dynamic-row__field">
                                <label class="mini-label" for="anggota_<?= (int) $i ?>_bidang">Bidang / Kelas</label>
                                <input id="anggota_<?= (int) $i ?>_bidang" name="anggota[<?= (int) $i ?>][bidang_kelas]" type="text"
                                       value="<?= e($row['bidang_kelas'] ?? '') ?>" placeholder="cth. Teknik Informatika / 3A"
                                       maxlength="100" required
                                       aria-invalid="<?= $err((int) $i, 'bidang_kelas') !== null ? 'true' : 'false' ?>"
                                       <?= $err((int) $i, 'bidang_kelas') !== null ? 'aria-describedby="err-anggota-' . (int) $i . '-bidang"' : '' ?>>
                                <?php if ($err((int) $i, 'bidang_kelas') !== null): ?><p class="field-error" id="err-anggota-<?= (int) $i ?>-bidang" role="alert"><?= e($err((int) $i, 'bidang_kelas')) ?></p><?php endif; ?>
                            </div>
                            <button type="button" class="row-remove" data-remove-row aria-label="Hapus baris <?= (int) $i + 1 ?>">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="dynamic-actions">
                    <button type="button" class="btn btn--add" data-add-row>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        Tambah Anggota
                    </button>
                    <a class="add-fallback" href="<?= url('/langkah/2?rows=' . $nextRows) ?>" data-nojs-only>Tambah baris (tanpa JavaScript)</a>
                    <span class="dynamic-counter" data-counter><?= count($rows) ?> / 10 baris</span>
                </div>
            </div>

            <template id="row-template-anggota">
                <div class="dynamic-row" data-row>
                    <span class="row-index" data-row-index>__N__</span>
                    <div class="dynamic-row__field">
                        <label class="mini-label" for="anggota___I___nama">Nama &amp; Gelar</label>
                        <input id="anggota___I___nama" name="anggota[__I__][nama]" type="text" placeholder="cth. Budi Santoso, S.Kom." minlength="3" maxlength="100" required>
                    </div>
                    <div class="dynamic-row__field">
                        <label class="mini-label" for="anggota___I___nip">NIP / NPM</label>
                        <input id="anggota___I___nip" name="anggota[__I__][nip_npm]" type="text" placeholder="NIP 18 digit / NPM 8–15 digit" inputmode="numeric" maxlength="18" required data-digits>
                    </div>
                    <div class="dynamic-row__field">
                        <label class="mini-label" for="anggota___I___bidang">Bidang / Kelas</label>
                        <input id="anggota___I___bidang" name="anggota[__I__][bidang_kelas]" type="text" placeholder="cth. Teknik Informatika / 3A" maxlength="100" required>
                    </div>
                    <button type="button" class="row-remove" data-remove-row aria-label="Hapus baris">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                    </button>
                </div>
            </template>

            <div class="form-actions">
                <div class="actions-buttons">
                    <a class="btn btn--ghost" href="<?= url('/langkah/1') ?>">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
                        Kembali
                    </a>
                    <button class="btn btn--primary" type="submit">
                        Lanjut ke rincian
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
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
