<?php
/** @var string|null $flash */
/** @var string $username */
?>
<div class="auth-wrap">
    <section class="auth-card" aria-labelledby="login-title">
        <div class="auth-card__art" aria-hidden="true">
            <div class="auth-card__art-inner">
                <p class="eyebrow">Panel Pengelola</p>
                <h2>Tinjau &amp; setujui laporan penelitian.</h2>
                <p>Masuk untuk melihat daftar laporan perkembangan penelitian yang masuk dari seluruh fakultas.</p>
                <ul class="auth-points">
                    <li>Daftar &amp; pencarian laporan</li>
                    <li>Detail lengkap tim dan luaran</li>
                    <li>Persetujuan ganda untuk biaya tinggi</li>
                </ul>
            </div>
            <div class="auth-card__bubble" aria-hidden="true"></div>
        </div>
        <div class="auth-card__form">
            <h1 id="login-title">Masuk Admin</h1>
            <p class="auth-sub">Gunakan akun yang diberikan oleh LPPM.</p>

            <?php if (!empty($flash)): ?>
                <div class="alert alert--error" role="alert"><strong>Gagal masuk.</strong><span><?= e($flash) ?></span></div>
            <?php endif; ?>

            <form method="post" action="<?= url('/admin/login') ?>" class="auth-form" novalidate>
                <?= csrf_field() ?>
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" value="<?= e($username) ?>" autocomplete="username"
                           maxlength="50" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input id="password" name="password" type="password" autocomplete="current-password"
                               minlength="8" maxlength="200" required>
                        <button type="button" class="password-toggle" data-toggle-password aria-label="Tampilkan password">Lihat</button>
                    </div>
                </div>
                <button class="btn btn--primary btn--block" type="submit">Masuk</button>
            </form>
            <p class="auth-foot">Akses dicatat untuk keperluan audit. Jangan bagikan kredensial Anda.</p>
        </div>
    </section>
</div>
