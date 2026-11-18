<?php
/** @var string $content */
$appName = (string) config('app.name', 'SIMLAP-Riset');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="description" content="SIMLAP-Riset — Sistem Pelaporan Perkembangan Penelitian.">
    <title><?= e(($pageTitle ?? '') . ' — ' . $appName) ?></title>
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="page-public">
    <a class="skip-link" href="#public-main">Lewati ke konten</a>
    <div class="bg-scene" aria-hidden="true">
        <span class="blob blob--blue"></span>
        <span class="blob blob--purple"></span>
        <span class="blob blob--peach"></span>
        <span class="blob blob--mint"></span>
    </div>
    <header class="public-topbar">
        <a class="brand" href="<?= url('/') ?>">
            <span class="brand__mark" aria-hidden="true">SR</span>
            <span class="brand__text">
                <strong><?= e($appName) ?></strong>
                <small>Sistem Pelaporan Perkembangan Penelitian</small>
            </span>
        </a>
        <nav class="public-nav" aria-label="Navigasi utama">
            <a href="<?= url('/') ?>">Mulai Laporan</a>
            <a href="<?= url('/admin/login') ?>">Admin</a>
        </nav>
    </header>
    <main class="public-stage" id="public-main">
        <?= $content ?>
    </main>
    <footer class="public-footer">
        <p>&copy; <?= date('Y') ?> <?= e($appName) ?> · LPPM · Data pribadi dilindungi.</p>
    </footer>
    <script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
