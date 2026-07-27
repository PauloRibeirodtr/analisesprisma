<?php

use App\Models\CicloDoubleBranco;
use App\Models\JanelaPagamentoDoubleBranco;
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

it('retorna três próximas, cinco anteriores e o resumo do ciclo', function () {
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

    foreach (range(1, 6) as $indice) {
        JanelaPagamentoDoubleBranco::factory()->create([
            'origem_id' => 200 + $indice,
            'ciclo_id' => $ciclo->id,
            'inicio_em' => $agora->copy()->subMinutes($indice * 10 + 3),
            'fim_em' => $agora->copy()->subMinutes($indice * 10),
            'status' => $indice === 1
                ? StatusJanelaPagamentoDoubleBranco::Win
                : StatusJanelaPagamentoDoubleBranco::Loss,
            'tiros_na_janela' => 6,
            'tiros_acumulados_ciclo' => 12,
            'acerto_em' => $indice === 1
                ? $agora->copy()->subMinutes(10)
                : null,
            'finalizada_em' => $agora->copy()->subMinutes($indice * 10),
        ]);
    }

    $response = $this->actingAs(usuarioPainelDouble())
        ->getJson(route('blaze.double.janelas-pagamento'));

    $response
        ->assertSuccessful()
        ->assertJsonPath('tipo', 'double-branco-janelas-pagamento')
        ->assertJsonCount(3, 'proximas')
        ->assertJsonCount(5, 'anteriores')
        ->assertJsonPath('cicloAtual.totalTiros', 12)
        ->assertJsonPath('cicloAtual.sequenciaLosses', 1)
        ->assertJsonPath('ultimoWin.totalTiros', 12);
});
