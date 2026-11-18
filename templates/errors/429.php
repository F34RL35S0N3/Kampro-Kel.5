<?php
$code = 429;
$title = $title ?? ($pageTitle ?? 'Terlalu banyak permintaan');
$message = $message ?? 'Batas percobaan tercapai. Demi keamanan, mohon tunggu beberapa saat sebelum mencoba lagi.';
$backUrl = $backUrl ?? '/';
require __DIR__ . '/_card.php';
