<?php

use App\Models\CicloDoubleBranco;
use App\Models\JanelaPagamentoDoubleBranco;
use App\Models\PeriodoRecuperacaoDoubleBranco;
use App\Models\User;
use App\StatusCicloDoubleBranco;
use App\StatusJanelaPagamentoDoubleBranco;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function usuarioPainelDouble(): User
{
    $usuario = new User([
        'name' => 'Cliente Double',
        'email' => 'double@example.com',
        'perfil' => 'cliente',
    ]);
    $usuario->forceFill([
        'id' => 99,
        'email_verified_at' => now(),
    ]);

    return $usuario;
}

it('protege a API das janelas com autenticação', function () {
    $this->getJson(route('blaze.double.janelas-pagamento'))
        ->assertUnauthorized();
});

it('retorna três próximas, dez anteriores e o resumo do ciclo', function () {
    $agora = now()->startOfMinute();
    $ciclo = CicloDoubleBranco::factory()->create([
        'status' => StatusCicloDoubleBranco::Aberto,
        'iniciado_em' => $agora->copy()->subHour(),
        'total_janelas' => 2,
        'total_tiros' => 12,
        'total_losses' => 1,
    ]);

    foreach (range(1, 3) as $indice) {
        JanelaPagamentoDoubleBranco::factory()->create([
            'origem_id' => 100 + $indice,
            'inicio_em' => $agora->copy()->addMinutes($indice * 10),
            'fim_em' => $agora->copy()->addMinutes($indice * 10 + 3)
                ->subMillisecond(),
        ]);
    }

    foreach (range(1, 11) as $indice) {
        $cicloEncerrado = CicloDoubleBranco::factory()->create([
            'status' => StatusCicloDoubleBranco::Win,
            'iniciado_em' => $agora->copy()->subMinutes($indice * 10 + 3),
            'encerrado_em' => $agora->copy()->subMinutes($indice * 10),
            'total_janelas' => 1,
            'total_tiros' => 12,
            'acerto_em' => $agora->copy()->subMinutes($indice * 10),
        ]);
        JanelaPagamentoDoubleBranco::factory()->create([
            'origem_id' => 200 + $indice,
            'ciclo_id' => $cicloEncerrado->id,
            'inicio_em' => $agora->copy()->subMinutes($indice * 10 + 3),
            'fim_em' => $agora->copy()->subMinutes($indice * 10),
            'status' => StatusJanelaPagamentoDoubleBranco::Win,
            'tiros_na_janela' => 6,
            'tiros_acumulados_ciclo' => 12,
            'acerto_em' => $agora->copy()->subMinutes($indice * 10),
            'finalizada_em' => $agora->copy()->subMinutes($indice * 10),
        ]);
    }

    $response = $this->actingAs(usuarioPainelDouble())
        ->getJson(route('blaze.double.janelas-pagamento'));

    $response
        ->assertSuccessful()
        ->assertJsonPath('tipo', 'double-branco-janelas-pagamento')
        ->assertJsonCount(3, 'proximas')
        ->assertJsonCount(10, 'anteriores')
        ->assertJsonPath('anteriores.0.posicaoAnterior', 1)
        ->assertJsonPath('anteriores.0.origemId', '201')
        ->assertJsonPath('cicloAtual.totalTiros', 12)
        ->assertJsonPath('cicloAtual.sequenciaLosses', 1)
        ->assertJsonPath('ultimoWin.totalTiros', 12);
});

it('oculta losses de ciclos encerrados e mantém losses do ciclo aberto', function () {
    $agora = now()->startOfMinute();
    $cicloEncerrado = CicloDoubleBranco::factory()->create([
        'status' => StatusCicloDoubleBranco::Win,
        'iniciado_em' => $agora->copy()->subHour(),
        'encerrado_em' => $agora->copy()->subMinutes(20),
    ]);
    $cicloAberto = CicloDoubleBranco::factory()->create([
        'status' => StatusCicloDoubleBranco::Aberto,
        'iniciado_em' => $agora->copy()->subMinutes(15),
    ]);
    $lossEncerrado = JanelaPagamentoDoubleBranco::factory()->create([
        'origem_id' => 301,
        'ciclo_id' => $cicloEncerrado->id,
        'inicio_em' => $agora->copy()->subMinutes(33),
        'fim_em' => $agora->copy()->subMinutes(30),
        'status' => StatusJanelaPagamentoDoubleBranco::Loss,
    ]);
    $winEncerrado = JanelaPagamentoDoubleBranco::factory()->create([
        'origem_id' => 302,
        'ciclo_id' => $cicloEncerrado->id,
        'inicio_em' => $agora->copy()->subMinutes(23),
        'fim_em' => $agora->copy()->subMinutes(20),
        'status' => StatusJanelaPagamentoDoubleBranco::Win,
        'tiros_acumulados_ciclo' => 14,
        'acerto_em' => $agora->copy()->subMinutes(20),
    ]);
    $lossAberto = JanelaPagamentoDoubleBranco::factory()->create([
        'origem_id' => 303,
        'ciclo_id' => $cicloAberto->id,
        'inicio_em' => $agora->copy()->subMinutes(13),
        'fim_em' => $agora->copy()->subMinutes(10),
        'status' => StatusJanelaPagamentoDoubleBranco::Loss,
    ]);

    $anteriores = $this->actingAs(usuarioPainelDouble())
        ->getJson(route('blaze.double.janelas-pagamento'))
        ->assertSuccessful()
        ->json('anteriores');

    expect(collect($anteriores)->pluck('id')->all())
        ->toBe([$lossAberto->id, $winEncerrado->id])
        ->not->toContain($lossEncerrado->id);
});

it('informa quando a plataforma está em recuperação', function () {
    PeriodoRecuperacaoDoubleBranco::factory()->create([
        'iniciado_em' => now()->subMinutes(2),
        'primeiro_branco_resultado_id' => 40,
        'primeiro_branco_idblaze' => 'primeiro-branco-painel',
        'primeiro_branco_em' => now()->subMinute(),
    ]);

    $this->actingAs(usuarioPainelDouble())
        ->getJson(route('blaze.double.janelas-pagamento'))
        ->assertSuccessful()
        ->assertJsonPath('recuperacao.ativa', true)
        ->assertJsonPath('recuperacao.brancosRecebidos', 1)
        ->assertJsonPath('recuperacao.brancosNecessarios', 2);
});
