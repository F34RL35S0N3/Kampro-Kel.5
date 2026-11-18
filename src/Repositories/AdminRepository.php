<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class AdminRepository
{
    public static function findByUsername(string $username): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ? AND is_active = 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ? AND is_active = 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function authenticate(string $username, string $password): ?array
    {
        $admin = self::findByUsername($username);
        if ($admin === null) {
            // Tetap jalankan verifikasi palsu untuk mengurangi timing oracle.
            password_verify($password, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
            return null;
        }
        if (!password_verify($password, (string) $admin['password_hash'])) {
            return null;
        }
        return $admin;
    }
}
