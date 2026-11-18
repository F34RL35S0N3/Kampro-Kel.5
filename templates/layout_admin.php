<?php
/** @var string $content */
/** @var array $admin */
$appName = (string) config('app.name', 'SIMLAP-Riset');
$active = $activeNav ?? 'list';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?= e(($pageTitle ?? 'Admin') . ' — ' . $appName) ?></title>
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="page-admin">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <a class="brand brand--admin" href="<?= url('/admin/laporan') ?>">
                <span class="brand__mark" aria-hidden="true">SR</span>
                <span class="brand__text">
                    <strong><?= e($appName) ?></strong>
                    <small>Panel Admin</small>
                </span>
            </a>
            <nav class="admin-nav" aria-label="Navigasi admin">
                <a href="<?= url('/admin/laporan') ?>" class="<?= $active === 'list' ? 'is-active' : '' ?>">
                    <span class="nav-dot" aria-hidden="true"></span> Daftar Laporan
                </a>
                <a href="<?= url('/') ?>" target="_blank" rel="noopener">
                    <span class="nav-dot" aria-hidden="true"></span> Formulir Publik
                </a>
            </nav>
            <div class="admin-user">
                <div class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) ($admin['username'] ?? 'A'), 0, 1))) ?></div>
                <div>
                    <strong><?= e($admin['username'] ?? 'admin') ?></strong>
                    <small><?= e(ucfirst((string) ($admin['role'] ?? 'reviewer'))) ?></small>
                </div>
            </div>
            <form method="post" action="<?= url('/admin/logout') ?>" class="logout-form">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--ghost btn--block">Keluar</button>
            </form>
        </aside>
        <div class="admin-main">
            <header class="admin-topbar">
                <h1><?= e($pageTitle ?? 'Panel Admin') ?></h1>
                <span class="admin-topbar__hint">Semua data pribadi hanya untuk petugas berwenang.</span>
            </header>
            <div class="admin-content">
                <?= $content ?>
            </div>
        </div>
    </div>
    <script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
