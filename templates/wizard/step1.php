<?php
/** @var int $step */
/** @var array $form */
/** @var array $errors */
/** @var string $flowToken */
/** @var int $completed */
$progressPct = (int) round(($step / 5) * 100);
$err = static fn (string $key): ?string => isset($errors[$key]) ? (string) $errors[$key] : null;
$fieldClass = static fn (string $key): string => isset($errors[$key]) ? ' field--error' : '';
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
                <noscript><a class="icon-close" href="<?= url('/') ?>" aria-label="Batalkan dan kembali">&times;</a></noscript>
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
                <div class="alert alert--error" role="alert">
                    <strong>Terjadi kesalahan.</strong>
                    <span><?= e($err('_form')) ?></span>
                </div>
            <?php endif; ?>

            <div class="form-grid">
                <div class="field<?= $fieldClass('ketua_nama') ?>">
                    <label for="ketua_nama">Nama Lengkap <span class="req" aria-hidden="true">*</span></label>
                    <input id="ketua_nama" name="ketua_nama" type="text" value="<?= e($form['ketua_nama']) ?>"
                           placeholder="cth. Dr. Ayu Lestari, S.Kom., M.T." autocomplete="name"
                           minlength="3" maxlength="100" required
                           aria-invalid="<?= $err('ketua_nama') !== null ? 'true' : 'false' ?>"
                           <?= $err('ketua_nama') !== null ? 'aria-describedby="err-ketua_nama"' : '' ?>>
                    <?php if ($err('ketua_nama') !== null): ?><p class="field-error" id="err-ketua_nama" role="alert"><?= e($err('ketua_nama')) ?></p><?php endif; ?>
                </div>

                <div class="field<?= $fieldClass('ketua_jabatan') ?>">
                    <label for="ketua_jabatan">Jabatan <span class="req" aria-hidden="true">*</span></label>
                    <input id="ketua_jabatan" name="ketua_jabatan" type="text" value="<?= e($form['ketua_jabatan']) ?>"
                           placeholder="cth. Lektor Kepala / Ketua Tim" autocomplete="organization-title"
                           minlength="2" maxlength="100" required
                           aria-invalid="<?= $err('ketua_jabatan') !== null ? 'true' : 'false' ?>"
                           <?= $err('ketua_jabatan') !== null ? 'aria-describedby="err-ketua_jabatan"' : '' ?>>
                    <?php if ($err('ketua_jabatan') !== null): ?><p class="field-error" id="err-ketua_jabatan" role="alert"><?= e($err('ketua_jabatan')) ?></p><?php endif; ?>
                </div>

                <div class="field<?= $fieldClass('ketua_nip') ?>">
                    <label for="ketua_nip">NIP <span class="req" aria-hidden="true">*</span></label>
                    <input id="ketua_nip" name="ketua_nip" type="text" value="<?= e($form['ketua_nip']) ?>"
                           placeholder="18 digit angka" inputmode="numeric" autocomplete="off"
                           pattern="[0-9]{18}" minlength="18" maxlength="18" required
                           data-digits data-digits-length="18"
                           aria-invalid="<?= $err('ketua_nip') !== null ? 'true' : 'false' ?>"
                           <?= $err('ketua_nip') !== null ? 'aria-describedby="err-ketua_nip"' : '' ?>>
                    <p class="field-hint">Tanpa spasi. NIP ASN terdiri dari 18 digit.</p>
                    <?php if ($err('ketua_nip') !== null): ?><p class="field-error" id="err-ketua_nip" role="alert"><?= e($err('ketua_nip')) ?></p><?php endif; ?>
                </div>

                <div class="field<?= $fieldClass('ketua_fakultas') ?>">
                    <label for="ketua_fakultas">Fakultas/Sekolah <span class="req" aria-hidden="true">*</span></label>
                    <input id="ketua_fakultas" name="ketua_fakultas" type="text" value="<?= e($form['ketua_fakultas']) ?>"
                           placeholder="cth. Fakultas Teknik" autocomplete="organization"
                           minlength="2" maxlength="150" required
                           aria-invalid="<?= $err('ketua_fakultas') !== null ? 'true' : 'false' ?>"
                           <?= $err('ketua_fakultas') !== null ? 'aria-describedby="err-ketua_fakultas"' : '' ?>>
                    <?php if ($err('ketua_fakultas') !== null): ?><p class="field-error" id="err-ketua_fakultas" role="alert"><?= e($err('ketua_fakultas')) ?></p><?php endif; ?>
                </div>

                <div class="field<?= $fieldClass('ketua_kelompok_keilmuan') ?>">
                    <label for="ketua_kelompok_keilmuan">Kelompok Keilmuan <span class="req" aria-hidden="true">*</span></label>
                    <input id="ketua_kelompok_keilmuan" name="ketua_kelompok_keilmuan" type="text" value="<?= e($form['ketua_kelompok_keilmuan']) ?>"
                           placeholder="cth. Rekayasa Perangkat Lunak" minlength="2" maxlength="150" required
                           aria-invalid="<?= $err('ketua_kelompok_keilmuan') !== null ? 'true' : 'false' ?>"
                           <?= $err('ketua_kelompok_keilmuan') !== null ? 'aria-describedby="err-ketua_kelompok_keilmuan"' : '' ?>>
                    <?php if ($err('ketua_kelompok_keilmuan') !== null): ?><p class="field-error" id="err-ketua_kelompok_keilmuan" role="alert"><?= e($err('ketua_kelompok_keilmuan')) ?></p><?php endif; ?>
                </div>

                <div class="field<?= $fieldClass('ketua_telp') ?>">
                    <label for="ketua_telp">Telp/HP <span class="req" aria-hidden="true">*</span></label>
                    <input id="ketua_telp" name="ketua_telp" type="tel" value="<?= e($form['ketua_telp']) ?>"
                           placeholder="cth. 0812 3456 7890" autocomplete="tel"
                           inputmode="tel" maxlength="20" required
                           data-phone
                           aria-invalid="<?= $err('ketua_telp') !== null ? 'true' : 'false' ?>"
                           <?= $err('ketua_telp') !== null ? 'aria-describedby="err-ketua_telp"' : '' ?>>
                    <p class="field-hint">Boleh diawali 08, 628, atau +628.</p>
                    <?php if ($err('ketua_telp') !== null): ?><p class="field-error" id="err-ketua_telp" role="alert"><?= e($err('ketua_telp')) ?></p><?php endif; ?>
                </div>

                <div class="field field--wide<?= $fieldClass('ketua_email') ?>">
                    <label for="ketua_email">E-mail <span class="req" aria-hidden="true">*</span></label>
                    <input id="ketua_email" name="ketua_email" type="email" value="<?= e($form['ketua_email']) ?>"
                           placeholder="cth. ayu.lestari@kampus.ac.id" autocomplete="email"
                           maxlength="254" required
                           aria-invalid="<?= $err('ketua_email') !== null ? 'true' : 'false' ?>"
                           <?= $err('ketua_email') !== null ? 'aria-describedby="err-ketua_email"' : '' ?>>
                    <?php if ($err('ketua_email') !== null): ?><p class="field-error" id="err-ketua_email" role="alert"><?= e($err('ketua_email')) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <div class="actions-buttons">
                    <button class="btn btn--primary" type="submit">
                        Lanjut ke anggota tim
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                    </button>
                </div>
                <div class="help-card">
                    <span class="help-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 21l2.2-5.2A8.5 8.5 0 1 1 21 11.5Z"/></svg>
                    </span>
                    <div>
                        <strong>Butuh bantuan?</strong>
                        <a href="https://wa.me/<?= e(preg_replace('/\D/', '', (string) config('app.lppm_whatsapp'))) ?>" target="_blank" rel="noopener">
                            Hubungi LPPM via WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>
<?php require __DIR__ . '/_cancel_dialog.php'; ?>
