<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Audit & log aplikasi. Nilai PII tidak pernah ditulis — hanya nama field,
 * kode kejadian, dan hash (PRD §9.4).
 */
final class Logger
{
    public static function event(string $event, array $context = []): void
    {
        $dir = (string) config('app.logs_path');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $safe = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $safe[$key] = is_string($value) ? mb_substr($value, 0, 200) : $value;
            } else {
                $safe[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        $line = sprintf(
            "[%s] %s %s%s",
            date('c'),
            preg_replace('/[^A-Z0-9_]/', '', strtoupper($event)) ?: 'EVENT',
            json_encode($safe, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            PHP_EOL
        );

        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }

    public static function warning(string $event, array $context = []): void
    {
        self::event('WARN.' . $event, $context);
    }

    public static function error(string $event, array $context = []): void
    {
        self::event('ERROR.' . $event, $context);
    }
}
