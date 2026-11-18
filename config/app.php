<?php
declare(strict_types=1);

use App\Support\Env;

return [
    'name'                 => Env::get('APP_NAME', 'SIMLAP-Riset'),
    'tagline'              => 'Laporan Perkembangan Penelitian',
    'env'                  => Env::get('APP_ENV', 'production'),
    'debug'                => Env::get('APP_DEBUG', '0') === '1',
    'url'                  => Env::get('APP_URL', ''),
    'timezone'             => 'Asia/Jakarta',
    'key'                  => Env::get('APP_KEY', 'insecure-default-app-key-change-me-please'),
    'hmac_key'             => Env::get('APP_HMAC_KEY', Env::get('APP_KEY', 'insecure-default-hmac-key-change-me')),
    'lppm_whatsapp'        => Env::get('LPPM_WHATSAPP', '6281234567890'),
    'lppm_email'           => Env::get('LPPM_EMAIL', 'lppm@kampus.ac.id'),
    'enable_dual_approval' => Env::get('ENABLE_DUAL_APPROVAL', '1') === '1',
    'enable_captcha'       => Env::get('ENABLE_CAPTCHA', '0') === '1',
    'captcha_site_key'     => Env::get('CAPTCHA_SITE_KEY', ''),
    'captcha_secret'       => Env::get('CAPTCHA_SECRET', ''),
    'logs_path'            => BASE_PATH . '/storage/logs',
    'session'              => [
        'name'     => 'SIMLAPRISETSESS',
        'lifetime' => 7200,
    ],
];
