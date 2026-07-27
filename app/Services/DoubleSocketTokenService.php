<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class DoubleSocketTokenService
{
    /** @return array{token: string, expires_at: int, ttl: int} */
    public function generate(User $user): array
    {
        $secret = (string) config('double.ws_jwt_secret');
        $ttl = max(60, min((int) config('double.ws_token_ttl', 1200), 3600));
        $issuedAt = time();
        $expiresAt = $issuedAt + $ttl;

        if (Str::length($secret) < 32) {
            throw new RuntimeException('A integração com o WebSocket Double não está configurada.');
        }

        $payload = [
            'user_id' => $user->getKey(),
            'origem' => (string) config('double.ws_origin', 'analisesprisma'),
            'feed' => 'double',
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ];

        return [
            'token' => $this->sign($payload, $secret),
            'expires_at' => $expiresAt,
            'ttl' => $ttl,
        ];
    }

    /**
     * @param  array{user_id: mixed, origem: string, feed: string, iat: int, exp: int}  $payload
     *
     * @throws JsonException
     */
    private function sign(array $payload, string $secret): string
    {
        $segments = [
            $this->base64UrlEncode(json_encode([
                'typ' => 'JWT',
                'alg' => 'HS256',
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)),
        ];

        $segments[] = $this->base64UrlEncode(
            hash_hmac('sha256', implode('.', $segments), $secret, true)
        );

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
