<?php
declare(strict_types=1);

use App\Core\Csrf;

if (!function_exists('e')) {
    /**
     * Output encoding wajib untuk semua variabel di template
     * (PRD A9.1). Tidak pernah echo data mentah.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('normalize_utf8')) {
    /**
     * Encoding & Sanitization (V1): normalisasi ke UTF-8 dan buang kontrol tak terlihat
     * yang berisiko, tanpa mengubah makna input bisnis.
     */
    function normalize_utf8(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map('normalize_utf8', $value);
        }
        if (!is_string($value)) {
            return $value;
        }
        $s = $value;
        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }
        $s = str_replace(["\0", "\r\n", "\r"], ["", "\n", "\n"], $s);
        // Hapus C0 control chars kecuali tab dan LF.
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? $s;
        if (class_exists('Normalizer')) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_C) ?: $s;
        }
        return $s;
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        $config = $GLOBALS['config'] ?? [];
        if ($key === null) {
            return $config;
        }
        $segments = explode('.', $key);
        $value = $config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        static $base = null;
        if ($base === null) {
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
            $dir = str_replace('\\', '/', dirname($script));
            $dir = rtrim($dir, '/');
            $base = ($dir === '.' || $dir === '/') ? '' : $dir;
        }
        $path = '/' . ltrim($path, '/');
        return $base . ($path === '/' ? '/' : $path);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return base_url($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return base_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
    }
}

if (!function_exists('rupiah')) {
    function rupiah(int|string $value): string
    {
        return 'Rp ' . number_format((int) $value, 0, ',', '.');
    }
}

if (!function_exists('step_label')) {
    function step_label(int $step): string
    {
        return [
            1 => 'Ketua Tim',
            2 => 'Anggota Tim',
            3 => 'Rincian Penelitian',
            4 => 'Target Luaran',
            5 => 'Tinjau & Kirim',
        ][$step] ?? '';
    }
}

if (!function_exists('step_title')) {
    function step_title(int $step): string
    {
        return [
            1 => 'Isi data ketua tim sebagai langkah awal.',
            2 => 'Siapa saja anggota yang terlibat?',
            3 => 'Ceritakan rincian rencana penelitian Anda.',
            4 => 'Tetapkan target luaran yang ingin dicapai.',
            5 => 'Tinjau kembali sebelum mengirim laporan.',
        ][$step] ?? '';
    }
}

if (!function_exists('step_subtitle')) {
    function step_subtitle(int $step): string
    {
        return [
            1 => 'Data ini digunakan untuk verifikasi dan korespondensi LPPM.',
            2 => 'Tambahkan 1 sampai 10 anggota tim riset/inovasi.',
            3 => 'Pastikan durasi, biaya, dan konteks penelitian saling konsisten.',
            4 => 'Pilih jenis luaran dan jumlah target yang realistis.',
            5 => 'Periksa seluruh data, lalu setujui pernyataan kebenaran data.',
        ][$step] ?? '';
    }
}

if (!function_exists('flash_set')) {
    function flash_set(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }
}

if (!function_exists('flash_get')) {
    function flash_get(string $key, mixed $default = null): mixed
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }
        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}

if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('request_ip')) {
    function request_ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}

if (!function_exists('ip_hash')) {
    function ip_hash(string $ip, string $salt): string
    {
        // AA-08: hanya korelasi pembatasan, bukan pelacakan lintas situs.
        return hash_hmac('sha256', $ip, $salt);
    }
}

if (!function_exists('user_agent_hash')) {
    function user_agent_hash(string $salt): string
    {
        return hash_hmac('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '', $salt);
    }
}
