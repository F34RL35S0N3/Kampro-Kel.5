<?php
declare(strict_types=1);

/*
 |--------------------------------------------------------------------------
 | SIMLAP-Riset — front controller
 |--------------------------------------------------------------------------
 | Semua permintaan non-aset diarahkan ke file ini (lihat .htaccess/router.php).
 */

require dirname(__DIR__) . '/src/bootstrap.php';

(new App\Core\Application())->run();
