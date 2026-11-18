<?php
declare(strict_types=1);

/*
 |--------------------------------------------------------------------------
 | SIMLAP-Riset — installer & seeding admin
 |--------------------------------------------------------------------------
 | Jalankan dari terminal (root proyek):
 |   C:\xampp\php\php.exe database/install.php
 |
 | Installer sengaja hanya dapat dijalankan lewat CLI agar tidak menjadi
 | permukaan serangan tambahan di produksi.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Installer hanya dapat dijalankan lewat CLI.\n";
    echo "Jalankan: php database/install.php\n";
    exit(1);
}

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;
use App\Support\Env;

function out(string $line = ''): void
{
    echo $line . PHP_EOL;
}

function fail(string $message): never
{
    fwrite(STDERR, '[GAGAL] ' . $message . PHP_EOL);
    exit(1);
}

function splitSql(string $sql): array
{
    $lines = preg_split('/\R/', $sql) ?: [];
    $clean = [];
    foreach ($lines as $line) {
        $trimmed = ltrim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--')) {
            continue;
        }
        $clean[] = $line;
    }
    $statements = preg_split('/;\s*(?:\R|$)/', implode("\n", $clean)) ?: [];
    return array_values(array_filter(array_map('trim', $statements), static fn ($s) => $s !== ''));
}

out('=====================================================');
out(' SIMLAP-Riset — instalasi basis data');
out('=====================================================');

if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    fail('Butuh PHP 8.2+, versi saat ini ' . PHP_VERSION . '.');
}

foreach (['pdo', 'mbstring'] as $extension) {
    if (!extension_loaded($extension)) {
        fail('Ekstensi PHP wajib tidak tersedia: ' . $extension . '.');
    }
}

$driver = (string) config('db.driver', 'mysql');
$schemaFile = BASE_PATH . '/database/schema.' . ($driver === 'sqlite' ? 'sqlite' : 'mysql') . '.sql';
if (!is_file($schemaFile)) {
    fail('Skema tidak ditemukan: ' . $schemaFile);
}

out('Driver  : ' . $driver);

if ($driver === 'sqlite') {
    $sqlitePath = (string) config('db.sqlite_path');
    $dir = dirname($sqlitePath);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        fail('Tidak dapat membuat direktori: ' . $dir);
    }
    out('Berkas  : ' . $sqlitePath);
    $pdo = Database::pdo();
} else {
    $host = (string) config('db.host', '127.0.0.1');
    $port = (int) config('db.port', 3306);
    $name = (string) config('db.database', 'simlap_riset');
    $user = (string) config('db.username', 'root');
    $pass = (string) config('db.password', '');

    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        fail('Nama database hanya boleh huruf, angka, dan garis bawah.');
    }

    out('Server  : ' . $host . ':' . $port);
    out('Database: ' . $name);

    try {
        $bootstrapPdo = new PDO(
            sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $bootstrapPdo->exec(
            'CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        $bootstrapPdo = null;
    } catch (PDOException $e) {
        fail('Tidak dapat membuat/menyambung database MySQL: ' . $e->getMessage());
    }

    Database::reset();
    $pdo = Database::pdo();
}

out('Menerapkan skema...');
$sql = (string) file_get_contents($schemaFile);
$statements = splitSql($sql);
try {
    $pdo->beginTransaction();
    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fail('Gagal menerapkan skema: ' . $e->getMessage());
}
out('  OK — ' . count($statements) . ' pernyataan SQL diproses.');

$accounts = [
    [
        'username' => 'admin',
        'password' => (string) Env::get('ADMIN_PASSWORD', 'Admin#12345'),
        'role'     => 'reviewer',
    ],
    [
        'username' => 'admin2',
        'password' => (string) Env::get('ADMIN2_PASSWORD', 'Admin#67890'),
        'role'     => 'reviewer',
    ],
];

out('Menyiapkan akun admin...');
foreach ($accounts as $account) {
    $hash = password_hash($account['password'], PASSWORD_DEFAULT);
    if ($hash === false) {
        fail('Gagal membuat password hash.');
    }

    if ($driver === 'sqlite') {
        $stmt = $pdo->prepare(
            'INSERT INTO admin_users (username, password_hash, role, is_active, created_at)
             VALUES (?, ?, ?, 1, ?)
             ON CONFLICT(username) DO UPDATE SET
                password_hash = excluded.password_hash,
                role = excluded.role,
                is_active = 1'
        );
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO admin_users (username, password_hash, role, is_active, created_at)
             VALUES (?, ?, ?, 1, ?)
             ON DUPLICATE KEY UPDATE
                password_hash = VALUES(password_hash),
                role = VALUES(role),
                is_active = 1'
        );
    }

    try {
        $stmt->execute([$account['username'], $hash, $account['role'], now()]);
        out('  OK — ' . $account['username']);
    } catch (Throwable $e) {
        fail('Gagal membuat akun ' . $account['username'] . ': ' . $e->getMessage());
    }
}

$logs = BASE_PATH . '/storage/logs';
if (!is_dir($logs)) {
    @mkdir($logs, 0775, true);
}

out();
out('Instalasi selesai.');
out();
out('Akun default (ubah password sebelum produksi):');
out('  admin  / ' . (string) Env::get('ADMIN_PASSWORD', 'Admin#12345'));
out('  admin2 / ' . (string) Env::get('ADMIN2_PASSWORD', 'Admin#67890'));
out();
out('Jalankan server pengembangan:');
out('  C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public public/router.php');
out('Buka http://127.0.0.1:8000');
out();
