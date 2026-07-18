<?php

use App\Models\Assinatura;
use App\Models\Pagamento;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Date;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Date::setTestNow('2026-07-17 12:00:00');
    $this->administrador = User::factory()->create([
        'perfil' => 'administrador',
    ]);
    $this->cliente = User::factory()->create([
        'perfil' => 'cliente',
        'name' => 'Cliente Renovação',
    ]);
    $this->plano = Plano::factory()->create([
        'nome' => 'Plano Renovável',
    ]);
    $this->assinatura = Assinatura::factory()
        ->for($this->cliente, 'cliente')
        ->for($this->plano)
        ->create([
            'nome_plano' => 'Plano Renovável',
            'valor_referencia' => '99.90',
            'duracao_dias' => 30,
            'inicio_em' => '2026-07-01 12:00:00',
            'termino_em' => '2026-07-31 12:00:00',
            'cancelada_em' => null,
        ]);
});

afterEach(function () {
    Date::setTestNow();
});

it('protects renewal by authentication and administrative profile', function () {
    $rota = route(
        'admin.clientes.assinaturas.renovacao.create',
        [$this->cliente, $this->assinatura],
    );

    $this->get($rota)
        ->assertRedirect(route('login'));

    $this->actingAs($this->cliente)
        ->get($rota)
        ->assertForbidden();
});

it('allows administrators and analysts to open the renewal screen', function (string $perfil) {
    $responsavel = User::factory()->create(['perfil' => $perfil]);

    $this->actingAs($responsavel)
        ->get(route(
            'admin.clientes.assinaturas.renovacao.create',
            [$this->cliente, $this->assinatura],
        ))
        ->assertSuccessful()
        ->assertSee('Renovar assinatura')
        ->assertSee('Plano Renovável')
        ->assertSee('31/07/2026 12:00')
        ->assertSee('name="dias_adicionados"', false)
        ->assertSee('name="valor_recebido"', false)
        ->assertSee('name="data_pagamento"', false)
        ->assertSee('name="forma_pagamento"', false)
        ->assertDontSee('name="nova_data_inicio"', false);
})->with([
    'administrator' => 'administrador',
    'analyst' => 'analista',
]);

it('requires and shows a new starting date for an expired subscription', function () {
    $this->assinatura->update([
        'inicio_em' => '2026-05-01 12:00:00',
        'termino_em' => '2026-05-31 12:00:00',
    ]);

    $this->actingAs($this->administrador)
        ->get(route(
            'admin.clientes.assinaturas.renovacao.create',
            [$this->cliente, $this->assinatura],
        ))
        ->assertSuccessful()
        ->assertSee('Vencida')
        ->assertSee('name="nova_data_inicio"', false)
        ->assertSee('Reinício do acesso');

    $this->actingAs($this->administrador)
        ->post(route(
            'admin.clientes.assinaturas.renovacao.store',
            [$this->cliente, $this->assinatura],
        ), [
            'dias_adicionados' => 15,
            'valor_recebido' => '79.90',
            'data_pagamento' => '2026-07-17 11:00:00',
            'forma_pagamento' => 'pix',
            'nova_data_inicio' => '',
        ])
        ->assertSessionHasErrors('nova_data_inicio');
});

it('renews a current subscription and preserves other history', function () {
    $pagamentoAnterior = Pagamento::factory()
        ->for($this->cliente, 'cliente')
        ->for($this->assinatura)
        ->for($this->administrador, 'registradoPor')
        ->create([
            'valor' => '99.90',
            'tipo' => 'contratacao',
        ]);
    $pagamentoAnteriorOriginal = $pagamentoAnterior->getRawOriginal();
    $outraAssinatura = Assinatura::factory()
        ->for($this->cliente, 'cliente')
        ->create();
    $outraAssinaturaOriginal = $outraAssinatura->getRawOriginal();

    $this->actingAs($this->administrador)
        ->post(route(
            'admin.clientes.assinaturas.renovacao.store',
            [$this->cliente, $this->assinatura],
        ), [
            'dias_adicionados' => 20,
            'valor_recebido' => '89.50',
            'data_pagamento' => '2026-07-17 11:30:00',
            'forma_pagamento' => 'transferencia',
            'observacao' => 'Renovação negociada por vinte dias.',
        ])
        ->assertRedirect(route('admin.clientes.show', $this->cliente))
        ->assertSessionHas(
            'sucesso',
            'Assinatura renovada e pagamento registrado com sucesso.',
        );

    $this->assinatura->refresh();
    $pagamentoNovo = $this->assinatura->pagamentos()
        ->where('tipo', 'renovacao')
        ->sole();

    expect($this->assinatura)
        ->inicio_em->toDateTimeString()->toBe('2026-07-01 12:00:00')
        ->termino_em->toDateTimeString()->toBe('2026-08-20 12:00:00')
        ->and($pagamentoNovo)
        ->valor->toBe('89.50')
        ->forma_pagamento->toBe('transferencia')
        ->registrado_por->toBe($this->administrador->id)
        ->observacao->toBe('Renovação negociada por vinte dias.')
        ->and($pagamentoAnterior->fresh()->getRawOriginal())
        ->toEqual($pagamentoAnteriorOriginal)
        ->and($outraAssinatura->fresh()->getRawOriginal())
        ->toEqual($outraAssinaturaOriginal);
});

it('restarts an expired subscription on the chosen date', function () {
    $this->assinatura->update([
        'inicio_em' => '2026-05-01 12:00:00',
        'termino_em' => '2026-05-31 12:00:00',
    ]);

    $this->actingAs($this->administrador)
        ->post(route(
            'admin.clientes.assinaturas.renovacao.store',
            [$this->cliente, $this->assinatura],
        ), [
            'dias_adicionados' => 15,
            'valor_recebido' => '79.90',
            'data_pagamento' => '2026-07-17 11:00:00',
            'forma_pagamento' => 'pix',
            'nova_data_inicio' => '2026-07-20 08:00:00',
        ])
        ->assertRedirect(route('admin.clientes.show', $this->cliente));

    expect($this->assinatura->fresh())
        ->inicio_em->toDateTimeString()->toBe('2026-07-20 08:00:00')
        ->termino_em->toDateTimeString()->toBe('2026-08-04 08:00:00')
        ->and(Pagamento::query()->sole()->tipo)->toBe('renovacao');
});

it('uses the correct action for each subscription situation', function () {
    $vencida = Assinatura::factory()
        ->vencida()
        ->for($this->cliente, 'cliente')
        ->create();
    $futura = Assinatura::factory()
        ->futura()
        ->for($this->cliente, 'cliente')
        ->create();
    $cancelada = Assinatura::factory()
        ->cancelada()
        ->for($this->cliente, 'cliente')
        ->create();

    $this->actingAs($this->administrador)
        ->get(route('admin.clientes.show', $this->cliente))
        ->assertSuccessful()
        ->assertSee(route(
            'admin.clientes.assinaturas.renovacao.create',
            [$this->cliente, $this->assinatura],
        ), false)
        ->assertSee(route(
            'admin.clientes.assinaturas.renovacao.create',
            [$this->cliente, $vencida],
        ), false)
        ->assertDontSee(route(
            'admin.clientes.assinaturas.renovacao.create',
            [$this->cliente, $futura],
        ), false)
        ->assertSee(route(
            'admin.clientes.assinaturas.reativacao.create',
            [$this->cliente, $cancelada],
        ), false);
});

it('redirects canceled and future subscriptions to the proper guidance', function () {
    $this->assinatura->update(['cancelada_em' => now()]);

    $this->actingAs($this->administrador)
        ->get(route(
            'admin.clientes.assinaturas.renovacao.create',
            [$this->cliente, $this->assinatura],
        ))
        ->assertRedirect(route(
            'admin.clientes.assinaturas.reativacao.create',
            [$this->cliente, $this->assinatura],
        ));

    $this->assinatura->update([
        'cancelada_em' => null,
        'inicio_em' => now()->addDay(),
        'termino_em' => now()->addDays(31),
    ]);

    $this->actingAs($this->administrador)
        ->get(route(
            'admin.clientes.assinaturas.renovacao.create',
            [$this->cliente, $this->assinatura],
        ))
        ->assertRedirect(route('admin.clientes.show', $this->cliente))
        ->assertSessionHas(
            'erro',
            'A assinatura ainda não iniciou e não pode ser renovada.',
        );
});

it('rejects invalid renewal data without financial changes', function () {
    $this->actingAs($this->administrador)
        ->post(route(
            'admin.clientes.assinaturas.renovacao.store',
            [$this->cliente, $this->assinatura],
        ), [
            'dias_adicionados' => 0,
            'valor_recebido' => '-1',
            'data_pagamento' => '',
            'forma_pagamento' => 'boleto',
            'observacao' => str_repeat('a', 2001),
        ])
        ->assertSessionHasErrors([
            'dias_adicionados',
            'valor_recebido',
            'data_pagamento',
            'forma_pagamento',
            'observacao',
        ]);

    expect(Pagamento::query()->count())->toBe(0);
});

it('prevents manipulating a subscription through another customer url', function () {
    $outroCliente = User::factory()->create(['perfil' => 'cliente']);

    $this->actingAs($this->administrador)
        ->get(route(
            'admin.clientes.assinaturas.renovacao.create',
            [$outroCliente, $this->assinatura],
        ))
        ->assertNotFound();
});
