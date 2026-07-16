<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ChillbetWheelService
{
    /** @return array{total: int, resultados: list<array{id: int, gameId: int, cor: string, data: string}>} */
    public function latest(int $limit): array
    {
        $baseUrl = rtrim((string) config('chillbet.api_url'), '/');
        $apiToken = (string) config('chillbet.api_token');

        if ($baseUrl === '' || $apiToken === '') {
            throw new RuntimeException('A integração com a API ChillBet não está configurada.');
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->withHeaders(['x-api-token' => $apiToken])
                ->connectTimeout((int) config('chillbet.connect_timeout', 3))
                ->timeout((int) config('chillbet.timeout', 8))
                ->retry(
                    [100, 300],
                    when: fn (Throwable $exception): bool => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && $exception->response->serverError()),
                    throw: false,
                )
                ->get('/api/wheel/ultimos', ['limit' => $limit]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Não foi possível conectar à API ChillBet.', previous: $exception);
        }

        if ($response->unauthorized() || $response->forbidden()) {
            throw new RuntimeException('A API ChillBet rejeitou as credenciais configuradas.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('A API ChillBet está indisponível no momento.');
        }

        $payload = $response->json();

        if (! is_array($payload) || ! is_array($payload['resultados'] ?? null)) {
            throw new RuntimeException('A API ChillBet retornou uma resposta inválida.');
        }

        $resultados = collect($payload['resultados'])
            ->filter(fn (mixed $resultado): bool => is_array($resultado))
            ->map(fn (array $resultado): array => [
                'id' => (int) ($resultado['id'] ?? $resultado['gameId'] ?? 0),
                'gameId' => (int) ($resultado['gameId'] ?? 0),
                'cor' => strtoupper((string) ($resultado['cor'] ?? '')),
                'data' => (string) ($resultado['data'] ?? ''),
            ])
            ->filter(fn (array $resultado): bool => $resultado['gameId'] > 0
                && in_array($resultado['cor'], ['BLACK', 'RED', 'BLUE', 'GREEN'], true)
                && $resultado['data'] !== '')
            ->values()
            ->all();

        return [
            'total' => count($resultados),
            'resultados' => $resultados,
        ];
    }
}
