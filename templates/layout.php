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
    <meta name="description" content="Formulir daring Laporan Perkembangan Penelitian — SIMLAP-Riset.">
    <title><?= e(($pageTitle ?? '') . ' — ' . $appName) ?></title>
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="page-wizard">
    <a class="skip-link" href="#form-panel">Lewati ke formulir</a>
    <div class="bg-scene" aria-hidden="true">
        <span class="blob blob--blue"></span>
        <span class="blob blob--purple"></span>
        <span class="blob blob--peach"></span>
        <span class="blob blob--mint"></span>
    </div>
    <main class="wizard-stage">
        <?= $content ?>
    </main>
    <script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
