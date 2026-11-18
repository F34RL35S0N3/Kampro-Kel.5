<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Normalisasi input sebelum validasi (PRD §6.1 poin 3).
 * Semua input dari $_POST adalah string; konversi numerik dilakukan setelah
 * lolos regex digit — tidak pernah sebelum itu.
 */
final class Normalizer
{
    public static function text(mixed $value, bool $multiline = false): string
    {
        $value = (string) $value;
        if ($value === '' || !mb_check_encoding($value, 'UTF-8')) {
            return '';
        }

        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_C);
            if ($normalized !== false) {
                $value = $normalized;
            }
        }

        if ($multiline) {
            $value = str_replace(["\r\n", "\r"], "\n", $value);
            // Hapus karakter kontrol kecuali newline (0x0A) dan tab.
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
            $value = preg_replace('/[ \t]{2,}/u', ' ', $value) ?? '';
            $value = preg_replace('/\n{3,}/u', "\n\n", $value) ?? '';
            $value = preg_replace('/[ \t]+\n/u', "\n", $value) ?? '';
            return trim($value);
        }

        $value = preg_replace('/\p{C}+/u', '', $value) ?? '';
        $value = preg_replace('/[ \t]{2,}/u', ' ', $value) ?? '';
        return trim($value);
    }

    public static function digits(mixed $value): string
    {
        return preg_replace('/[^\d]/', '', (string) $value) ?? '';
    }

    public static function phoneId(mixed $value): string
    {
        $digits = preg_replace('/[^\d+]/', '', (string) $value) ?? '';
        if (str_starts_with($digits, '+62')) {
            return $digits;
        }
        if (str_starts_with($digits, '62')) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '+62' . substr($digits, 1);
        }
        return $digits;
    }

    public static function lower(mixed $value): string
    {
        return mb_strtolower(self::text($value), 'UTF-8');
    }

    public static function email(mixed $value): string
    {
        return mb_strtolower(self::text($value), 'UTF-8');
    }

    public static function integer(string $digits): int
    {
        return (int) ltrim($digits, '0') ?: 0;
    }

    /** Panjang teks (karakter, bukan byte) untuk batas LIM-08. */
    public static function length(mixed $value): int
    {
        return mb_strlen((string) $value, 'UTF-8');
    }

    public static function lineCount(string $value): int
    {
        if ($value === '') {
            return 0;
        }
        return substr_count($value, "\n") + 1;
    }
}
