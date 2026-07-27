<?php

use App\Actions\DoubleBranco\SincronizarJanelasPagamentoCrash;
use App\Models\JanelaPagamentoDoubleBranco;
use App\Services\CrashWindowService;
use App\StatusJanelaPagamentoDoubleBranco;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config([
        'double.crash_api_url' => 'https://crash.test',
        'double.crash_api_token' => 'token-de-teste',
        'double.crash_api_future_limit' => 4,
        'double.crash_api_previous_limit' => 100,
    ]);

    Http::preventStrayRequests();
});

it('sincroniza janelas anteriores e futuras sem apagar a apuração local', function () {
    $existente = JanelaPagamentoDoubleBranco::factory()->create([
        'origem_id' => 10,
        'status' => StatusJanelaPagamentoDoubleBranco::Loss,
        'tiros_na_janela' => 6,
    ]);

    Http::fake([
        'https://crash.test/api/crash/janelas-pagamento*' => Http::response([
            'anteriores' => [[
                'id' => '10',
                'inicioEm' => '2026-07-27T20:00:00.000Z',
                'fimEm' => '2026-07-27T20:02:59.999Z',
                'quantidadeVelas' => 2,
                'maiorMultiplicador' => '25.00',
            ]],
            'janelas' => [[
                'id' => '11',
                'inicioEm' => '2026-07-28T01:00:00.000Z',
                'fimEm' => '2026-07-28T01:02:59.999Z',
                'quantidadeVelas' => 1,
                'maiorMultiplicador' => '12.50',
            ]],
        ]),
    ]);

    $quantidade = app(SincronizarJanelasPagamentoCrash::class)->handle();

    expect($quantidade)->toBe(2);

    $existente->refresh();
    expect($existente->status)
        ->toBe(StatusJanelaPagamentoDoubleBranco::Loss)
        ->and($existente->tiros_na_janela)->toBe(6)
        ->and($existente->quantidade_velas_crash)->toBe(2)
        ->and($existente->maior_multiplicador)->toBe('25.00');

    $nova = JanelaPagamentoDoubleBranco::query()
        ->where('origem_id', 11)
        ->firstOrFail();

    expect($nova->status)
        ->toBe(StatusJanelaPagamentoDoubleBranco::Agendada)
        ->and($nova->inicio_em->toISOString())
        ->toStartWith('2026-07-28T01:00:00');

    Http::assertSent(fn (Request $request): bool => (
        $request->url()
            === 'https://crash.test/api/crash/janelas-pagamento?limit=4&anteriores=100'
        && $request->hasHeader('x-api-token', 'token-de-teste')
    ));
});

it('rejeita uma resposta inválida da API Crash', function () {
    Http::fake([
        'https://crash.test/*' => Http::response([
            'janelas' => null,
            'anteriores' => [],
        ]),
    ]);

    expect(fn () => app(CrashWindowService::class)->buscar())
        ->toThrow(
            RuntimeException::class,
            'A API de janelas Crash retornou uma resposta inválida.',
        );
});
