<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Token alur (AA-05/AA-06, V2.4.2) — menandatangani (session_id | step | iat)
 * sehingga klien tidak dapat memalsukan langkah maupun waktu.
 */
final class Hmac
{
    public static function sign(string $data): string
    {
        return hash_hmac('sha256', $data, (string) config('app.hmac_key'));
    }

    public static function verify(string $data, string $signature): bool
    {
        return hash_equals(self::sign($data), $signature);
    }

    /** Terbitkan token untuk satu langkah wizard. */
    public static function issueFlowToken(int $step, string $sessionId): string
    {
        $payload = [
            'step'  => $step,
            'iat'   => time(),
            'nonce' => bin2hex(random_bytes(8)),
        ];
        $encoded = self::base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        return $encoded . '.' . self::sign($sessionId . '|' . $encoded);
    }

    /**
     * @return array{step:int,iat:int,nonce:string}|null Null bila tidak sah/kedaluwarsa.
     */
    public static function verifyFlowToken(string $token, int $expectedStep, string $sessionId): ?array
    {
        if (!str_contains($token, '.') || strlen($token) > 512) {
            return null;
        }
        [$encoded, $signature] = explode('.', $token, 2);
        if (!self::verify($sessionId . '|' . $encoded, $signature)) {
            return null;
        }
        $json = self::base64UrlDecode($encoded);
        if ($json === null) {
            return null;
        }
        $payload = json_decode($json, true);
        if (!is_array($payload) || !isset($payload['step'], $payload['iat'], $payload['nonce'])) {
            return null;
        }
        if ((int) $payload['step'] !== $expectedStep) {
            return null;
        }
        $ttl = (int) config('limits.flow_token_ttl', 7200);
        $age = time() - (int) $payload['iat'];
        if ($age < 0 || $age > $ttl) {
            return null;
        }
        return [
            'step'  => (int) $payload['step'],
            'iat'   => (int) $payload['iat'],
            'nonce' => (string) $payload['nonce'],
        ];
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): ?string
    {
        $padded = $data . str_repeat('=', (4 - strlen($data) % 4) % 4);
        $decoded = base64_decode(strtr($padded, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }
}
