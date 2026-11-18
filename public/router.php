<?php
declare(strict_types=1);

/*
 |--------------------------------------------------------------------------
 | Router untuk PHP built-in server (pengembangan)
 |--------------------------------------------------------------------------
 | Jalankan dari root proyek:
 |   php -S 127.0.0.1:8000 -t public public/router.php
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, rawurldecode($path));

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
