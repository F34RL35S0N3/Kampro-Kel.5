<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = (array) config('db');
        self::$driver = (string) ($cfg['driver'] ?? 'mysql');

        if (self::$driver === 'sqlite') {
            $path = (string) ($cfg['sqlite_path'] ?? BASE_PATH . '/storage/database.sqlite');
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $dsn = 'sqlite:' . $path;
            $user = null;
            $pass = null;
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'] ?? '127.0.0.1',
                (int) ($cfg['port'] ?? 3306),
                $cfg['database'] ?? 'simlap_riset',
                $cfg['charset'] ?? 'utf8mb4'
            );
            $user = (string) ($cfg['username'] ?? 'root');
            $pass = (string) ($cfg['password'] ?? '');
        }

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
            if (self::$driver === 'sqlite') {
                $pdo->exec('PRAGMA foreign_keys = ON');
                $pdo->exec('PRAGMA journal_mode = WAL');
            }
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Koneksi basis data gagal. Periksa konfigurasi .env atau jalankan database/install.php.',
                0,
                $e
            );
        }

        self::$pdo = $pdo;
        return self::$pdo;
    }

    public static function driver(): string
    {
        return self::$driver;
    }

    public static function isSqlite(): bool
    {
        if (self::$pdo === null) {
            self::pdo();
        }
        return self::$driver === 'sqlite';
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }
}
