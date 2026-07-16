<?php

namespace App\Services;

use App\Models\User;
use JsonException;
use RuntimeException;

class ChillbetWheelSocketTokenService
{
    /** @return array{token: string, expires_at: int, ttl: int} */
    public function generate(User $user): array
    {
        $secret = (string) config('chillbet.ws_jwt_secret');
        $ttl = max(60, min((int) config('chillbet.ws_token_ttl', 1200), 3600));
        $issuedAt = time();
        $expiresAt = $issuedAt + $ttl;

        if ($secret === '') {
            throw new RuntimeException('A integração com o WebSocket ChillBet não está configurada.');
        }

        $payload = [
            'user_id' => $user->getKey(),
            'origem' => (string) config('chillbet.ws_origin', 'analisesprisma'),
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
     * @param  array{user_id: mixed, origem: string, iat: int, exp: int}  $payload
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
