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
        'name' => 'Cliente Reativação',
    ]);
    $this->plano = Plano::factory()->create();
    $this->assinatura = Assinatura::factory()
        ->cancelada()
        ->for($this->cliente, 'cliente')
        ->for($this->plano)
        ->create([
            'nome_plano' => 'Plano Cancelado',
            'valor_referencia' => '149.90',
            'duracao_dias' => 30,
            'inicio_em' => '2026-06-01 12:00:00',
            'termino_em' => '2026-07-01 12:00:00',
            'cancelada_em' => '2026-06-20 10:00:00',
            'observacao' => 'Registro anterior preservado.',
        ]);
});

afterEach(function () {
    Date::setTestNow();
});

it('renders the explicit reactivation form for a canceled subscription', function () {
    $this->actingAs($this->administrador)
        ->get(route(
            'admin.clientes.assinaturas.reativacao.create',
            [$this->cliente, $this->assinatura],
        ))
        ->assertSuccessful()
        ->assertSee('Reativar assinatura')
        ->assertSee('Plano Cancelado')
        ->assertSee('20/06/2026 10:00')
        ->assertSee('name="inicio_em"', false)
        ->assertSee('name="duracao_dias"', false)
        ->assertSee('name="observacao"', false)
        ->assertSee('name="registrar_pagamento"', false)
        ->assertSee('name="confirmada"', false)
        ->assertSee('data-payment-toggle', false)
        ->assertSee('reativacao-assinatura-', false);
});

it('reactivates with payment and preserves the previous period in history', function () {
    $this->actingAs($this->administrador)
        ->post(route(
            'admin.clientes.assinaturas.reativacao.store',
            [$this->cliente, $this->assinatura],
        ), [
            'confirmada' => '1',
            'inicio_em' => '2026-07-20 08:00:00',
            'duracao_dias' => 45,
            'registrar_pagamento' => '1',
            'valor_recebido' => '129.90',
            'data_pagamento' => '2026-07-17 11:30:00',
            'forma_pagamento' => 'pix',
            'observacao' => 'Cliente solicitou retorno ao serviço.',
        ])
        ->assertRedirect(route('admin.clientes.show', $this->cliente))
        ->assertSessionHas(
            'sucesso',
            'Assinatura reativada e pagamento registrado com sucesso.',
        );

    $assinatura = $this->assinatura->fresh();
    $pagamento = Pagamento::query()->sole();

    expect($assinatura)
        ->inicio_em->toDateTimeString()->toBe('2026-07-20 08:00:00')
        ->termino_em->toDateTimeString()->toBe('2026-09-03 08:00:00')
        ->duracao_dias->toBe(45)
        ->cancelada_em->toBeNull()
        ->observacao->toContain('Registro anterior preservado.')
        ->observacao->toContain('Período anterior: 01/06/2026 12:00:00 até 01/07/2026 12:00:00')
        ->observacao->toContain('Cliente solicitou retorno ao serviço.')
        ->and($pagamento)
        ->tipo->toBe('renovacao')
        ->valor->toBe('129.90')
        ->forma_pagamento->toBe('pix')
        ->registrado_por->toBe($this->administrador->id);
});

it('reactivates as courtesy without creating a financial record', function () {
    $this->actingAs($this->administrador)
        ->post(route(
            'admin.clientes.assinaturas.reativacao.store',
            [$this->cliente, $this->assinatura],
        ), [
            'confirmada' => 'yes',
            'inicio_em' => '2026-07-17 12:00:00',
            'duracao_dias' => 7,
            'registrar_pagamento' => '0',
            'observacao' => 'Cortesia comercial autorizada.',
        ])
        ->assertRedirect(route('admin.clientes.show', $this->cliente))
        ->assertSessionHas(
            'sucesso',
            'Assinatura reativada como cortesia, sem lançamento financeiro.',
        );

    expect($this->assinatura->fresh())
        ->cancelada_em->toBeNull()
        ->inicio_em->toDateTimeString()->toBe('2026-07-17 12:00:00')
        ->termino_em->toDateTimeString()->toBe('2026-07-24 12:00:00')
        ->and(Pagamento::query()->count())->toBe(0);
});

it('requires confirmation justification and payment fields when selected', function () {
    $this->actingAs($this->administrador)
        ->post(route(
            'admin.clientes.assinaturas.reativacao.store',
            [$this->cliente, $this->assinatura],
        ), [
            'confirmada' => '0',
            'inicio_em' => '',
            'duracao_dias' => 0,
            'registrar_pagamento' => '1',
            'valor_recebido' => '',
            'data_pagamento' => '',
            'forma_pagamento' => '',
            'observacao' => 'curta',
        ])
        ->assertSessionHasErrors([
            'confirmada',
            'inicio_em',
            'duracao_dias',
            'valor_recebido',
            'data_pagamento',
            'forma_pagamento',
            'observacao',
        ]);

    expect($this->assinatura->fresh()->cancelada_em)->not->toBeNull()
        ->and(Pagamento::query()->count())->toBe(0);
});

it('redirects a non canceled subscription with guidance', function () {
    $this->assinatura->update(['cancelada_em' => null]);

    $this->actingAs($this->administrador)
        ->get(route(
            'admin.clientes.assinaturas.reativacao.create',
            [$this->cliente, $this->assinatura],
        ))
        ->assertRedirect(route('admin.clientes.show', $this->cliente))
        ->assertSessionHas(
            'erro',
            'Somente assinaturas canceladas podem ser reativadas.',
        );
});

it('rejects reactivation post when the subscription is no longer canceled', function () {
    $this->assinatura->update(['cancelada_em' => null]);

    $this->actingAs($this->administrador)
        ->from(route('admin.clientes.show', $this->cliente))
        ->post(route(
            'admin.clientes.assinaturas.reativacao.store',
            [$this->cliente, $this->assinatura],
        ), [
            'confirmada' => '1',
            'inicio_em' => '2026-07-17 12:00:00',
            'duracao_dias' => 30,
            'registrar_pagamento' => '0',
            'observacao' => 'Tentativa inválida de reativação.',
        ])
        ->assertRedirect(route('admin.clientes.show', $this->cliente))
        ->assertSessionHasErrors('assinatura');

    expect(Pagamento::query()->count())->toBe(0);
});

it('prevents reactivation through another customer url', function () {
    $outroCliente = User::factory()->create(['perfil' => 'cliente']);

    $this->actingAs($this->administrador)
        ->get(route(
            'admin.clientes.assinaturas.reativacao.create',
            [$outroCliente, $this->assinatura],
        ))
        ->assertNotFound();
});
