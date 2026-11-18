<?php
$retry = (int) ($retry ?? 0);
$backUrl = $backUrl ?? '/';
?>
<div class="error-wrap">
    <div class="error-card">
        <span class="error-code"><?= e((string) $code) ?></span>
        <h1><?= e($title) ?></h1>
        <p><?= e($message) ?></p>
        <?php if ($retry > 0): ?>
            <p class="error-retry">
                Silakan coba lagi dalam <strong data-countdown="<?= $retry ?>"><?= $retry ?></strong> detik.
            </p>
        <?php endif; ?>
        <?php if (!empty($detail)): ?>
            <details class="error-detail">
                <summary>Detail teknis (mode pengembangan)</summary>
                <pre><?= e($detail) ?></pre>
            </details>
        <?php endif; ?>
        <div class="error-actions">
            <a class="btn btn--primary" href="<?= url($backUrl) ?>">Kembali</a>
            <a class="btn btn--ghost" href="<?= url('/') ?>">Halaman awal</a>
        </div>
    </div>
</div>
