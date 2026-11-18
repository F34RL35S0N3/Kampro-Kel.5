<?php
/** @var int $step */
/** @var array $form */
/** @var array $errors */
/** @var string $flowToken */
$progressPct = (int) round(($step / 5) * 100);
$rows = $form['luaran'] ?? [];
$rows = array_values(array_map(static fn ($r) => is_array($r) ? $r : ['jenis' => '', 'nama' => '', 'jumlah' => ''], $rows));
if ($rows === []) {
    $rows = [['jenis' => '', 'nama' => '', 'jumlah' => '']];
}
$rowErrors = isset($errors['luaran']) && is_array($errors['luaran']) ? $errors['luaran'] : [];
$listError = isset($errors['luaran']) && is_string($errors['luaran']) ? $errors['luaran'] : null;
$err = static fn (int $i, string $key): ?string => isset($rowErrors[$i][$key]) ? (string) $rowErrors[$i][$key] : null;
$types = (array) config('validation.luaran_types', []);
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
                <div class="alert alert--error" role="alert"><strong>Periksa target luaran.</strong><span><?= e($listError) ?></span></div>
            <?php endif; ?>
            <?php if (isset($errors['total'])): ?>
                <div class="alert alert--error" role="alert"><strong>Jumlah luaran tidak wajar.</strong><span><?= e($errors['total']) ?></span></div>
            <?php endif; ?>
            <?php if (isset($errors['dasar'])): ?>
                <div class="alert alert--error" role="alert"><strong>Luaran belum sesuai.</strong><span><?= e($errors['dasar']) ?></span></div>
            <?php endif; ?>

            <div class="dynamic-table dynamic-table--luaran" data-dynamic="luaran" data-max="10">
                <div class="dynamic-head dynamic-head--luaran" aria-hidden="true">
                    <span>No</span><span>Jenis Luaran</span><span>Nama / Keterangan</span><span>Jumlah</span><span></span>
                </div>

                <div class="dynamic-body" data-rows>
                    <?php foreach ($rows as $i => $row): ?>
                        <div class="dynamic-row dynamic-row--luaran" data-row>
                            <span class="row-index" data-row-index><?= (int) $i + 1 ?></span>
                            <div class="dynamic-row__field">
                                <label class="mini-label" for="luaran_<?= (int) $i ?>_jenis">Jenis Luaran</label>
                                <div class="select-wrap select-wrap--sm">
                                    <select id="luaran_<?= (int) $i ?>_jenis" name="luaran[<?= (int) $i ?>][jenis]" required
                                            aria-invalid="<?= $err((int) $i, 'jenis') !== null ? 'true' : 'false' ?>"
                                            <?= $err((int) $i, 'jenis') !== null ? 'aria-describedby="err-luaran-' . (int) $i . '-jenis"' : '' ?>>
                                        <option value="">Pilih jenis</option>
                                        <?php foreach ($types as $value => $label): ?>
                                            <option value="<?= e($value) ?>" <?= ($row['jenis'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <svg class="select-wrap__chevron" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                                <?php if ($err((int) $i, 'jenis') !== null): ?><p class="field-error" id="err-luaran-<?= (int) $i ?>-jenis" role="alert"><?= e($err((int) $i, 'jenis')) ?></p><?php endif; ?>
                            </div>
                            <div class="dynamic-row__field">
                                <label class="mini-label" for="luaran_<?= (int) $i ?>_nama">Nama / Keterangan</label>
                                <input id="luaran_<?= (int) $i ?>_nama" name="luaran[<?= (int) $i ?>][nama]" type="text"
                                       value="<?= e($row['nama'] ?? '') ?>" placeholder="cth. Artikel Scopus Q3"
                                       maxlength="200"
                                       aria-invalid="<?= $err((int) $i, 'nama') !== null ? 'true' : 'false' ?>"
                                       <?= $err((int) $i, 'nama') !== null ? 'aria-describedby="err-luaran-' . (int) $i . '-nama"' : '' ?>>
                                <?php if ($err((int) $i, 'nama') !== null): ?><p class="field-error" id="err-luaran-<?= (int) $i ?>-nama" role="alert"><?= e($err((int) $i, 'nama')) ?></p><?php endif; ?>
                            </div>
                            <div class="dynamic-row__field">
                                <label class="mini-label" for="luaran_<?= (int) $i ?>_jumlah">Jumlah</label>
                                <input id="luaran_<?= (int) $i ?>_jumlah" name="luaran[<?= (int) $i ?>][jumlah]" type="text"
                                       value="<?= e($row['jumlah'] ?? '') ?>" placeholder="1–99" inputmode="numeric"
                                       maxlength="2" required data-digits
                                       aria-invalid="<?= $err((int) $i, 'jumlah') !== null ? 'true' : 'false' ?>"
                                       <?= $err((int) $i, 'jumlah') !== null ? 'aria-describedby="err-luaran-' . (int) $i . '-jumlah"' : '' ?>>
                                <?php if ($err((int) $i, 'jumlah') !== null): ?><p class="field-error" id="err-luaran-<?= (int) $i ?>-jumlah" role="alert"><?= e($err((int) $i, 'jumlah')) ?></p><?php endif; ?>
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
                        Tambah Luaran
                    </button>
                    <a class="add-fallback" href="<?= url('/langkah/4?rows=' . $nextRows) ?>" data-nojs-only>Tambah baris (tanpa JavaScript)</a>
                    <span class="dynamic-counter" data-counter><?= count($rows) ?> / 10 baris</span>
                </div>
            </div>

            <template id="row-template-luaran">
                <div class="dynamic-row dynamic-row--luaran" data-row>
                    <span class="row-index" data-row-index>__N__</span>
                    <div class="dynamic-row__field">
                        <label class="mini-label" for="luaran___I___jenis">Jenis Luaran</label>
                        <div class="select-wrap select-wrap--sm">
                            <select id="luaran___I___jenis" name="luaran[__I__][jenis]" required>
                                <option value="">Pilih jenis</option>
                                <?php foreach ($types as $value => $label): ?>
                                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <svg class="select-wrap__chevron" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </div>
                    </div>
                    <div class="dynamic-row__field">
                        <label class="mini-label" for="luaran___I___nama">Nama / Keterangan</label>
                        <input id="luaran___I___nama" name="luaran[__I__][nama]" type="text" placeholder="cth. Artikel Scopus Q3" maxlength="200">
                    </div>
                    <div class="dynamic-row__field">
                        <label class="mini-label" for="luaran___I___jumlah">Jumlah</label>
                        <input id="luaran___I___jumlah" name="luaran[__I__][jumlah]" type="text" placeholder="1–99" inputmode="numeric" maxlength="2" required data-digits>
                    </div>
                    <button type="button" class="row-remove" data-remove-row aria-label="Hapus baris">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                    </button>
                </div>
            </template>

            <div class="form-actions">
                <div class="actions-buttons">
                    <a class="btn btn--ghost" href="<?= url('/langkah/3') ?>">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
                        Kembali
                    </a>
                    <button class="btn btn--primary" type="submit">
                        Lanjut ke tinjauan
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
