<?php
/** @var int $step */
/** @var array $form */
/** @var array $errors */
/** @var string $flowToken */
$progressPct = (int) round(($step / 5) * 100);
$err = static fn (string $key): ?string => isset($errors[$key]) ? (string) $errors[$key] : null;
$fieldClass = static fn (string $key): string => isset($errors[$key]) ? ' field--error' : '';
$jenisOptions = (array) config('validation.jenis_penelitian', []);
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

            <?php if ($err('_form') !== null): ?>
                <div class="alert alert--error" role="alert"><strong>Terjadi kesalahan.</strong><span><?= e($err('_form')) ?></span></div>
            <?php endif; ?>

            <div class="form-grid">
                <div class="field<?= $fieldClass('lama_minggu') ?>">
                    <label for="lama_minggu">Lama Penelitian <span class="req" aria-hidden="true">*</span></label>
                    <div class="input-suffix">
                        <input id="lama_minggu" name="lama_minggu" type="text" value="<?= e($form['lama_minggu']) ?>"
                               placeholder="cth. 24" inputmode="numeric" maxlength="3" required data-digits
                               aria-invalid="<?= $err('lama_minggu') !== null ? 'true' : 'false' ?>"
                               <?= $err('lama_minggu') !== null ? 'aria-describedby="err-lama_minggu"' : '' ?>>
                        <span class="input-suffix__unit">minggu</span>
                    </div>
                    <p class="field-hint">Rentang 1–104 minggu.</p>
                    <?php if ($err('lama_minggu') !== null): ?><p class="field-error" id="err-lama_minggu" role="alert"><?= e($err('lama_minggu')) ?></p><?php endif; ?>
                </div>

                <div class="field<?= $fieldClass('biaya_rupiah') ?>">
                    <label for="biaya_rupiah">Biaya Penelitian <span class="req" aria-hidden="true">*</span></label>
                    <div class="input-prefix">
                        <span class="input-prefix__sign">Rp</span>
                        <input id="biaya_rupiah" name="biaya_rupiah" type="text" value="<?= e($form['biaya_rupiah']) ?>"
                               placeholder="cth. 25.000.000" inputmode="numeric" maxlength="20" required
                               data-rupiah
                               aria-invalid="<?= $err('biaya_rupiah') !== null ? 'true' : 'false' ?>"
                               <?= $err('biaya_rupiah') !== null ? 'aria-describedby="err-biaya_rupiah"' : '' ?>>
                    </div>
                    <p class="field-hint" data-rupiah-preview>Masukkan angka rupiah bulat (1 – 5.000.000.000).</p>
                    <?php if ($err('biaya_rupiah') !== null): ?><p class="field-error" id="err-biaya_rupiah" role="alert"><?= e($err('biaya_rupiah')) ?></p><?php endif; ?>
                </div>

                <div class="field<?= $fieldClass('jenis_penelitian') ?>">
                    <label for="jenis_penelitian">Jenis Penelitian <span class="req" aria-hidden="true">*</span></label>
                    <div class="select-wrap">
                        <select id="jenis_penelitian" name="jenis_penelitian" required
                                aria-invalid="<?= $err('jenis_penelitian') !== null ? 'true' : 'false' ?>"
                                <?= $err('jenis_penelitian') !== null ? 'aria-describedby="err-jenis_penelitian"' : '' ?>>
                            <option value="">Pilih jenis penelitian</option>
                            <?php foreach ($jenisOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $form['jenis_penelitian'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <svg class="select-wrap__chevron" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                    <?php if ($err('jenis_penelitian') !== null): ?><p class="field-error" id="err-jenis_penelitian" role="alert"><?= e($err('jenis_penelitian')) ?></p><?php endif; ?>
                </div>

                <div class="field<?= $fieldClass('tema_rip') ?>">
                    <label for="tema_rip">Tema RIP Terkait <span class="req" aria-hidden="true">*</span></label>
                    <input id="tema_rip" name="tema_rip" type="text" value="<?= e($form['tema_rip']) ?>"
                           placeholder="cth. Transformasi Digital & Kecerdasan Artifisial"
                           minlength="3" maxlength="500" required
                           aria-invalid="<?= $err('tema_rip') !== null ? 'true' : 'false' ?>"
                           <?= $err('tema_rip') !== null ? 'aria-describedby="err-tema_rip"' : '' ?>>
                    <?php if ($err('tema_rip') !== null): ?><p class="field-error" id="err-tema_rip" role="alert"><?= e($err('tema_rip')) ?></p><?php endif; ?>
                </div>

                <div class="field field--wide<?= $fieldClass('mitra') ?>">
                    <label for="mitra">Mitra Penelitian</label>
                    <input id="mitra" name="mitra" type="text" value="<?= e($form['mitra']) ?>"
                           placeholder="cth. PT Maju Bersama / Tidak ada" maxlength="300"
                           aria-invalid="<?= $err('mitra') !== null ? 'true' : 'false' ?>"
                           <?= $err('mitra') !== null ? 'aria-describedby="err-mitra"' : '' ?>>
                    <p class="field-hint">Wajib untuk penelitian terapan atau biaya ≥ Rp 100 juta. Tulis “Tidak ada” bila tidak ada mitra.</p>
                    <?php if ($err('mitra') !== null): ?><p class="field-error" id="err-mitra" role="alert"><?= e($err('mitra')) ?></p><?php endif; ?>
                </div>

                <div class="field field--wide<?= $fieldClass('cpl_prodi') ?>">
                    <label for="cpl_prodi">CPL Program Studi Terkait <span class="req" aria-hidden="true">*</span></label>
                    <textarea id="cpl_prodi" name="cpl_prodi" rows="4" maxlength="1000" required
                              placeholder="Tuliskan capaian pembelajaran lulusan yang terkait dengan penelitian ini…"
                              aria-invalid="<?= $err('cpl_prodi') !== null ? 'true' : 'false' ?>"
                              <?= $err('cpl_prodi') !== null ? 'aria-describedby="err-cpl_prodi"' : '' ?>><?= e($form['cpl_prodi']) ?></textarea>
                    <div class="field-meta"><span>5–1000 karakter, maksimal 20 baris.</span><span data-counter-for="cpl_prodi">0</span></div>
                    <?php if ($err('cpl_prodi') !== null): ?><p class="field-error" id="err-cpl_prodi" role="alert"><?= e($err('cpl_prodi')) ?></p><?php endif; ?>
                </div>

                <div class="field field--wide<?= $fieldClass('potensi_adopsi') ?>">
                    <label for="potensi_adopsi">Potensi Adopsi Luaran <span class="req" aria-hidden="true">*</span></label>
                    <textarea id="potensi_adopsi" name="potensi_adopsi" rows="4" maxlength="1000" required
                              placeholder="Jelaskan bagaimana hasil penelitian berpotensi diadopsi oleh masyarakat, industri, atau institusi…"
                              aria-invalid="<?= $err('potensi_adopsi') !== null ? 'true' : 'false' ?>"
                              <?= $err('potensi_adopsi') !== null ? 'aria-describedby="err-potensi_adopsi"' : '' ?>><?= e($form['potensi_adopsi']) ?></textarea>
                    <div class="field-meta"><span>10–1000 karakter.</span><span data-counter-for="potensi_adopsi">0</span></div>
                    <?php if ($err('potensi_adopsi') !== null): ?><p class="field-error" id="err-potensi_adopsi" role="alert"><?= e($err('potensi_adopsi')) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <div class="actions-buttons">
                    <a class="btn btn--ghost" href="<?= url('/langkah/2') ?>">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
                        Kembali
                    </a>
                    <button class="btn btn--primary" type="submit">
                        Lanjut ke target luaran
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
