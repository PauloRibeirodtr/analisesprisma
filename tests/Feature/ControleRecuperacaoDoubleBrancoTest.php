<?php

use App\Models\PeriodoRecuperacaoDoubleBranco;
use App\Services\ControleRecuperacaoDoubleBrancoService;
use App\Services\LeituraHistoricoDoubleService;
use App\StatusPeriodoRecuperacaoDoubleBranco;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\mock;

uses(LazilyRefreshDatabase::class);

function resultadoControleRecuperacao(
    int $id,
    int $roll,
    string $horario,
): array {
    return [
        'id' => $id,
        'idblaze' => "controle-{$id}",
        'roll' => $roll,
        'resultado_em' => CarbonImmutable::parse($horario)->utc(),
    ];
}

it('não inicia a recuperação antes de 35 resultados sem branco', function () {
    config(['double.tiros_sem_branco_para_recuperacao' => 35]);
    $resultados = collect(range(1, 34))->map(
        fn (int $id): array => resultadoControleRecuperacao(
            $id,
            1,
            "2026-07-27T10:{$id}:00Z",
        ),
    );

    mock(LeituraHistoricoDoubleService::class)
        ->shouldReceive('buscarAteGatilhoSemBranco')
        ->once()
        ->with(35)
        ->andReturn($resultados);

    expect(app(ControleRecuperacaoDoubleBrancoService::class)->atualizar())
        ->toBeNull()
        ->and(PeriodoRecuperacaoDoubleBranco::query()->count())->toBe(0);
});

it('inicia a recuperação exatamente no resultado 35 sem branco', function () {
    config(['double.tiros_sem_branco_para_recuperacao' => 35]);
    $resultados = collect(range(1, 35))->map(
        fn (int $id): array => resultadoControleRecuperacao(
            $id,
            1,
            CarbonImmutable::parse('2026-07-27T10:00:00Z')
                ->addSeconds($id * 30)
                ->toISOString(),
        ),
    );

    mock(LeituraHistoricoDoubleService::class)
        ->shouldReceive('buscarAteGatilhoSemBranco')
        ->once()
        ->with(35)
        ->andReturn($resultados);

    $periodo = app(ControleRecuperacaoDoubleBrancoService::class)->atualizar();

    expect($periodo)->not->toBeNull()
        ->and($periodo->status)->toBe(StatusPeriodoRecuperacaoDoubleBranco::Ativa)
        ->and($periodo->gatilho_resultado_id)->toBe(35)
        ->and($periodo->gatilho_idblaze)->toBe('controle-35');
});

it('encerra a recuperação somente depois de dois brancos', function () {
    $periodo = PeriodoRecuperacaoDoubleBranco::factory()->create([
        'gatilho_resultado_id' => 35,
        'gatilho_idblaze' => 'controle-35',
        'iniciado_em' => '2026-07-27 10:17:30',
    ]);
    $brancos = collect([
        resultadoControleRecuperacao(40, 0, '2026-07-27T10:20:00Z'),
        resultadoControleRecuperacao(47, 0, '2026-07-27T10:23:30Z'),
    ]);

    mock(LeituraHistoricoDoubleService::class)
        ->shouldReceive('buscarBrancosDepois')
        ->once()
        ->with(35, Mockery::type(CarbonImmutable::class), 2)
        ->andReturn($brancos);

    $resultado = app(ControleRecuperacaoDoubleBrancoService::class)->atualizar();

    expect($resultado->status)
        ->toBe(StatusPeriodoRecuperacaoDoubleBranco::Encerrada)
        ->and($resultado->primeiro_branco_resultado_id)->toBe(40)
        ->and($resultado->segundo_branco_resultado_id)->toBe(47)
        ->and($resultado->encerrado_em)->not->toBeNull()
        ->and($periodo->fresh()->status)
        ->toBe(StatusPeriodoRecuperacaoDoubleBranco::Encerrada);
});

it('permanece em recuperação depois do primeiro branco', function () {
    $periodo = PeriodoRecuperacaoDoubleBranco::factory()->create([
        'gatilho_resultado_id' => 35,
        'gatilho_idblaze' => 'controle-primeiro-35',
        'iniciado_em' => '2026-07-27 10:17:30',
    ]);

    mock(LeituraHistoricoDoubleService::class)
        ->shouldReceive('buscarBrancosDepois')
        ->once()
        ->andReturn(collect([
            resultadoControleRecuperacao(40, 0, '2026-07-27T10:20:00Z'),
        ]));

    $resultado = app(ControleRecuperacaoDoubleBrancoService::class)->atualizar();

    expect($resultado->status)
        ->toBe(StatusPeriodoRecuperacaoDoubleBranco::Ativa)
        ->and($resultado->primeiro_branco_resultado_id)->toBe(40)
        ->and($resultado->segundo_branco_resultado_id)->toBeNull()
        ->and($periodo->fresh()->encerrado_em)->toBeNull();
});
