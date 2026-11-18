<?php
declare(strict_types=1);

/*
 |--------------------------------------------------------------------------
 | SIMLAP-Riset — bootstrap aplikasi
 |--------------------------------------------------------------------------
 | Front controller memuat file ini. Tidak ada output sebelum ini.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/src/Support/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Support\Env;

Env::load(BASE_PATH . '/.env');

$GLOBALS['config'] = [
    'app'        => require BASE_PATH . '/config/app.php',
    'db'         => require BASE_PATH . '/config/db.php',
    'limits'     => require BASE_PATH . '/config/limits.php',
    'validation' => require BASE_PATH . '/config/validation.php',
];

date_default_timezone_set((string) config('app.timezone', 'Asia/Jakarta'));

// Error tidak pernah ditampilkan ke pengguna (PRD §9.1).
$debug = (bool) config('app.debug', false);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Header keamanan dasar (PRD §9.1).
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 0');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header(
        "Content-Security-Policy: default-src 'self'; " .
        "img-src 'self' data:; style-src 'self' 'unsafe-inline'; " .
        "script-src 'self'; font-src 'self' data:; connect-src 'self'; " .
        "frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
    );
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Konfigurasi sesi aman.
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.gc_maxlifetime', (string) (int) config('app.session.lifetime', 7200));
    session_name((string) config('app.session.name', 'SIMLAPRISETSESS'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
