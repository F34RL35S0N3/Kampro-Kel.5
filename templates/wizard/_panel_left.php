<?php
/** @var int $step */
/** @var array $wizard */
/** @var int $completed */
$current = min($completed + 1, 5);
$steps = [
    1 => ['Ketua Tim', 'Identitas penanggung jawab'],
    2 => ['Anggota Tim', 'Susunan tim riset'],
    3 => ['Rincian Penelitian', 'Durasi, biaya & konteks'],
    4 => ['Target Luaran', 'Capaian yang ditargetkan'],
    5 => ['Tinjau & Kirim', 'Periksa lalu kirim'],
];
?>
<aside class="panel-left" aria-label="Informasi dan progres pengisian">
    <div class="panel-left__glow" aria-hidden="true"></div>
    <svg class="panel-left__waves" viewBox="0 0 600 420" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <g fill="none" stroke="currentColor" stroke-width="1.2" opacity="0.35">
            <path d="M-40 330 C 80 250, 170 390, 300 320 S 480 250, 660 330"/>
            <path d="M-40 360 C 90 280, 180 420, 320 350 S 500 280, 680 360"/>
            <path d="M-40 390 C 100 310, 200 450, 340 380 S 520 310, 700 390"/>
            <path d="M-40 420 C 110 340, 220 480, 360 410 S 540 340, 720 420"/>
        </g>
    </svg>

    <div class="panel-left__content">
        <p class="eyebrow">Laporan Perkembangan Penelitian</p>
        <h1 class="panel-headline">Lengkapi data tim dan<br>rencana penelitian Anda.</h1>

        <ol class="stepper" aria-label="Langkah pengisian">
            <?php foreach ($steps as $num => [$title, $hint]): ?>
                <?php
                $isDone = $num <= $completed;
                $isActive = $num === $current && $num === $step;
                $isAccessible = $num <= $completed + 1;
                $state = $isDone ? 'is-done' : ($isActive ? 'is-active' : 'is-pending');
                if ($isActive) {
                    $state .= ' is-current';
                }
                ?>
                <li class="step-item <?= $state ?>">
                    <?php if ($isAccessible): ?>
                        <a class="step-item__link" href="<?= url('/langkah/' . $num) ?>"
                           <?= $num === $step ? 'aria-current="step"' : '' ?>>
                    <?php else: ?>
                        <div class="step-item__link" aria-disabled="true">
                    <?php endif; ?>
                            <span class="step-item__num" aria-hidden="true">
                                <?php if ($isDone): ?>
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                <?php else: ?>
                                    <?= $num ?>
                                <?php endif; ?>
                            </span>
                            <span class="step-item__body">
                                <strong><?= e($title) ?></strong>
                                <small><?= e($hint) ?></small>
                            </span>
                            <span class="step-item__state" aria-hidden="true"><?= $isDone ? 'Selesai' : ($isActive ? 'Aktif' : 'Menunggu') ?></span>
                    <?php if ($isAccessible): ?>
                        </a>
                    <?php else: ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>

        <p class="panel-note">
            Perkiraan waktu pengisian <strong>± 8–10 menit</strong>. Data tersimpan aman di server
            dan hanya dapat diakses oleh petugas LPPM.
        </p>

        <ul class="quick-links" aria-label="Bantuan">
            <li>
                <a href="https://wa.me/<?= e(preg_replace('/\D/', '', (string) config('app.lppm_whatsapp'))) ?>" target="_blank" rel="noopener">
                    <span class="quick-links__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 21l2.2-5.2A8.5 8.5 0 1 1 21 11.5Z"/></svg>
                    </span>
                    Kontak LPPM
                </a>
            </li>
            <li>
                <a href="mailto:<?= e((string) config('app.lppm_email')) ?>">
                    <span class="quick-links__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
                    </span>
                    Email
                </a>
            </li>
            <li>
                <a href="#form-panel">
                    <span class="quick-links__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 0 1 4.85.85c0 1.65-2.35 2.15-2.35 3.65"/><path d="M12 17h.01"/></svg>
                    </span>
                    Panduan
                </a>
            </li>
        </ul>
    </div>

    <div class="chat-bubble" aria-hidden="true">
        <svg viewBox="0 0 120 120" width="96" height="96" role="presentation" focusable="false">
            <defs>
                <linearGradient id="bubblePink" x1="20%" y1="0%" x2="80%" y2="100%">
                    <stop offset="0%" stop-color="#ff9ec2"/>
                    <stop offset="55%" stop-color="#f2679b"/>
                    <stop offset="100%" stop-color="#d94279"/>
                </linearGradient>
                <linearGradient id="bubbleShine" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#ffffff" stop-opacity="0.85"/>
                    <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
                </linearGradient>
            </defs>
            <path d="M60 18c24 0 42 16 42 37S84 92 60 92c-6 0-12-1-17-3l-19 9 4-17c-6-6-10-15-10-26 0-21 18-37 42-37Z" fill="url(#bubblePink)"/>
            <path d="M60 26c18 0 32 12 32 29S78 84 60 84c-5 0-10-1-14-3l-13 6 3-12c-5-5-8-12-8-20 0-17 14-29 32-29Z" fill="url(#bubbleShine)" opacity="0.35"/>
            <circle cx="44" cy="55" r="5" fill="#fff"/>
            <circle cx="60" cy="55" r="5" fill="#fff"/>
            <circle cx="76" cy="55" r="5" fill="#fff"/>
        </svg>
    </div>
</aside>
