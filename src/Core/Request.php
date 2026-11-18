<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = rawurldecode($uri);

        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = str_replace('\\', '/', dirname($script));
        $base = rtrim($base, '/');
        if ($base !== '' && $base !== '/' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = '/' . ltrim($uri, '/');
        $uri = preg_replace('#/+#', '/', $uri) ?: '/';
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }
        return $uri;
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return normalize_utf8($value);
    }

    public function post(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $_POST)) {
            return $default;
        }
        return normalize_utf8($_POST[$key]);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $_GET)) {
            return $default;
        }
        return normalize_utf8($_GET[$key]);
    }

    public function all(): array
    {
        return normalize_utf8($_POST);
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . str_replace('-', '_', strtoupper($name));
        $value = $_SERVER[$key] ?? null;
        return is_string($value) ? $value : null;
    }

    public function contentLength(): int
    {
        return (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    }

    public function isHttps(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }

    /** Batas ukuran body (LIM-06 / AA-03). */
    public function exceedsBodyLimit(int $limit): bool
    {
        return $this->contentLength() > $limit;
    }
}
