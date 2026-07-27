<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class CrashWindowService
{
    /**
     * @return Collection<int, array{
     *     origem_id: int,
     *     inicio_em: CarbonImmutable,
     *     fim_em: CarbonImmutable,
     *     quantidade_velas_crash: int,
     *     maior_multiplicador: string
     * }>
     */
    public function buscar(): Collection
    {
        $baseUrl = rtrim((string) config('double.crash_api_url'), '/');
        $apiToken = (string) config('double.crash_api_token');

        if ($baseUrl === '' || $apiToken === '') {
            throw new RuntimeException(
                'A integração com a API de janelas Crash não está configurada.',
            );
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->withHeaders(['x-api-token' => $apiToken])
                ->connectTimeout(
                    (int) config('double.crash_api_connect_timeout', 3),
                )
                ->timeout((int) config('double.crash_api_timeout', 8))
                ->retry(
                    [100, 300],
                    when: fn (Throwable $exception): bool => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException
                            && $exception->response->serverError()),
                    throw: false,
                )
                ->get('/api/crash/janelas-pagamento', [
                    'limit' => (int) config(
                        'double.crash_api_future_limit',
                        4,
                    ),
                    'anteriores' => (int) config(
                        'double.crash_api_previous_limit',
                        100,
                    ),
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Não foi possível conectar à API de janelas Crash.',
                previous: $exception,
            );
        }

        if ($response->unauthorized() || $response->forbidden()) {
            throw new RuntimeException(
                'A API de janelas Crash rejeitou as credenciais configuradas.',
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'A API de janelas Crash está indisponível no momento.',
            );
        }

        $payload = $response->json();

        if (
            ! is_array($payload)
            || ! is_array($payload['janelas'] ?? null)
            || ! is_array($payload['anteriores'] ?? null)
        ) {
            throw new RuntimeException(
                'A API de janelas Crash retornou uma resposta inválida.',
            );
        }

        return collect([
            ...$payload['anteriores'],
            ...$payload['janelas'],
        ])
            ->map(fn (mixed $janela): array => $this->normalizar($janela))
            ->unique('origem_id')
            ->values();
    }

    /**
     * @return array{
     *     origem_id: int,
     *     inicio_em: CarbonImmutable,
     *     fim_em: CarbonImmutable,
     *     quantidade_velas_crash: int,
     *     maior_multiplicador: string
     * }
     */
    private function normalizar(mixed $janela): array
    {
        if (! is_array($janela)) {
            throw new RuntimeException(
                'A API de janelas Crash retornou uma janela inválida.',
            );
        }

        $origemId = filter_var(
            Arr::get($janela, 'id'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );
        $quantidadeVelas = filter_var(
            Arr::get($janela, 'quantidadeVelas'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );
        $maiorMultiplicador = (string) Arr::get(
            $janela,
            'maiorMultiplicador',
            '',
        );

        try {
            $inicioEm = CarbonImmutable::parse(
                (string) Arr::get($janela, 'inicioEm', ''),
            )->utc();
            $fimEm = CarbonImmutable::parse(
                (string) Arr::get($janela, 'fimEm', ''),
            )->utc();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'A API de janelas Crash retornou horários inválidos.',
                previous: $exception,
            );
        }

        if (
            $origemId === false
            || $quantidadeVelas === false
            || ! is_numeric($maiorMultiplicador)
            || (float) $maiorMultiplicador < 10
            || $fimEm->lessThan($inicioEm)
        ) {
            throw new RuntimeException(
                'A API de janelas Crash retornou dados inválidos.',
            );
        }

        return [
            'origem_id' => $origemId,
            'inicio_em' => $inicioEm,
            'fim_em' => $fimEm,
            'quantidade_velas_crash' => $quantidadeVelas,
            'maior_multiplicador' => $maiorMultiplicador,
        ];
    }
}
