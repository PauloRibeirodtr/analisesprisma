<?php

use App\Models\CicloDoubleBranco;
use App\Models\JanelaPagamentoDoubleBranco;
use App\Services\ApuracaoJanelaDoubleBrancoService;
use App\Services\LeituraHistoricoDoubleService;
use App\StatusCicloDoubleBranco;
use App\StatusJanelaPagamentoDoubleBranco;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\mock;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config([
        'double.finalizacao_grace_seconds' => 0,
        'double.tiros_estimados_por_minuto' => 2,
    ]);
});

function resultadoDoubleTeste(
    int $id,
    int $roll,
    string $horario,
): array {
    return [
        'id' => $id,
        'idblaze' => "game-{$id}",
        'roll' => $roll,
        'resultado_em' => CarbonImmutable::parse($horario)->utc(),
    ];
}

it('continua o ciclo depois do loss e encerra no primeiro branco', function () {
    $primeira = JanelaPagamentoDoubleBranco::factory()->create([
        'origem_id' => 1,
        'inicio_em' => '2026-07-27 10:00:00',
        'fim_em' => '2026-07-27 10:02:59.999',
    ]);
    $segunda = JanelaPagamentoDoubleBranco::factory()->create([
        'origem_id' => 2,
        'inicio_em' => '2026-07-27 11:00:00',
        'fim_em' => '2026-07-27 11:02:59.999',
    ]);
    $terceira = JanelaPagamentoDoubleBranco::factory()->create([
        'origem_id' => 3,
        'inicio_em' => '2026-07-27 11:30:00',
        'fim_em' => '2026-07-27 11:32:59.999',
    ]);
    $resultadosPrimeira = collect([
        resultadoDoubleTeste(1, 1, '2026-07-27T10:00:10Z'),
        resultadoDoubleTeste(2, 2, '2026-07-27T10:00:40Z'),
        resultadoDoubleTeste(3, 3, '2026-07-27T10:01:10Z'),
        resultadoDoubleTeste(4, 4, '2026-07-27T10:01:40Z'),
        resultadoDoubleTeste(5, 5, '2026-07-27T10:02:10Z'),
        resultadoDoubleTeste(6, 6, '2026-07-27T10:02:40Z'),
    ]);
    $resultadosSegunda = collect([
        resultadoDoubleTeste(7, 8, '2026-07-27T11:00:10Z'),
        resultadoDoubleTeste(8, 9, '2026-07-27T11:00:40Z'),
        resultadoDoubleTeste(9, 10, '2026-07-27T11:01:10Z'),
        resultadoDoubleTeste(10, 11, '2026-07-27T11:01:40Z'),
        resultadoDoubleTeste(11, 0, '2026-07-27T11:02:10Z'),
        resultadoDoubleTeste(12, 12, '2026-07-27T11:02:40Z'),
    ]);
    $resultadosTerceira = collect([
        resultadoDoubleTeste(13, 4, '2026-07-27T11:30:10Z'),
        resultadoDoubleTeste(14, 9, '2026-07-27T11:30:40Z'),
    ]);

    mock(LeituraHistoricoDoubleService::class)
        ->shouldReceive('buscarEntre')
        ->times(3)
        ->andReturn(
            $resultadosPrimeira,
            $resultadosSegunda,
            $resultadosTerceira,
        );

    app(ApuracaoJanelaDoubleBrancoService::class)->apurar(
        CarbonImmutable::parse('2026-07-27T12:00:00Z'),
    );

    $primeira->refresh();
    $segunda->refresh();
    $terceira->refresh();
    [$cicloEncerrado, $cicloAtual] = CicloDoubleBranco::query()
        ->orderBy('id')
        ->get();

    expect($primeira->status)
        ->toBe(StatusJanelaPagamentoDoubleBranco::Loss)
        ->and($primeira->tiros_na_janela)->toBe(6)
        ->and($primeira->ordem_no_ciclo)->toBe(1)
        ->and($segunda->status)->toBe(StatusJanelaPagamentoDoubleBranco::Win)
        ->and($segunda->tiros_na_janela)->toBe(5)
        ->and($segunda->tiros_acumulados_ciclo)->toBe(11)
        ->and($segunda->ordem_no_ciclo)->toBe(2)
        ->and($cicloEncerrado->status)->toBe(StatusCicloDoubleBranco::Win)
        ->and($cicloEncerrado->total_janelas)->toBe(2)
        ->and($cicloEncerrado->total_tiros)->toBe(11)
        ->and($cicloEncerrado->total_losses)->toBe(1)
        ->and($cicloEncerrado->acerto_idblaze)->toBe('game-11')
        ->and($terceira->ciclo_id)->toBe($cicloAtual->id)
        ->and($terceira->ordem_no_ciclo)->toBe(1)
        ->and($terceira->tiros_na_janela)->toBe(2)
        ->and($cicloAtual->status)->toBe(StatusCicloDoubleBranco::Aberto)
        ->and($cicloAtual->total_tiros)->toBe(2);
});

it('estima dois tiros por minuto e mantém loss definitivo quando a fonte falha', function () {
    $janela = JanelaPagamentoDoubleBranco::factory()->create([
        'inicio_em' => '2026-07-27 10:00:00',
        'fim_em' => '2026-07-27 10:02:59.999',
    ]);

    mock(LeituraHistoricoDoubleService::class)
        ->shouldReceive('buscarEntre')
        ->once()
        ->andThrow(new RuntimeException('Banco indisponível'));

    app(ApuracaoJanelaDoubleBrancoService::class)->apurar(
        CarbonImmutable::parse('2026-07-27T10:03:10Z'),
    );

    $janela->refresh();

    expect($janela->status)
        ->toBe(StatusJanelaPagamentoDoubleBranco::Loss)
        ->and($janela->tiros_na_janela)->toBe(6)
        ->and($janela->tiros_acumulados_ciclo)->toBe(6)
        ->and($janela->fonte_indisponivel)->toBeTrue();

    mock(LeituraHistoricoDoubleService::class)
        ->shouldNotReceive('buscarEntre');

    app(ApuracaoJanelaDoubleBrancoService::class)->apurar(
        CarbonImmutable::parse('2026-07-27T10:10:00Z'),
    );

    expect($janela->fresh()->status)
        ->toBe(StatusJanelaPagamentoDoubleBranco::Loss);
});
