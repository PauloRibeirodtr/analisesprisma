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
        'name' => 'Cliente Prisma',
    ]);
});

afterEach(function () {
    Date::setTestNow();
});

it('protects plan contracting by authentication and administrative profile', function () {
    $url = route('admin.clientes.assinaturas.create', $this->cliente);

    $this->get($url)
        ->assertRedirect(route('login'));

    $this->actingAs($this->cliente)
        ->get($url)
        ->assertForbidden();
});

it('allows analysts to access the contracting screen', function () {
    $analista = User::factory()->create(['perfil' => 'analista']);

    $this->actingAs($analista)
        ->get(route('admin.clientes.assinaturas.create', $this->cliente))
        ->assertSuccessful()
        ->assertSee('Contratar plano');
});

it('shows only sale plans with defaults and identifies an existing current plan', function () {
    $planoDisponivel = Plano::factory()->create([
        'nome' => 'Plano Prisma Pro',
        'valor_padrao' => '149.90',
        'duracao_dias' => 45,
    ]);
    $planoVigente = Plano::factory()->create([
        'nome' => 'Plano já contratado',
    ]);
    Plano::factory()->create([
        'nome' => 'Plano indisponível',
        'disponivel_para_venda' => false,
    ]);
    Assinatura::factory()
        ->vigente()
        ->for($this->cliente, 'cliente')
        ->for($planoVigente)
        ->create();

    $this->actingAs($this->administrador)
        ->get(route('admin.clientes.assinaturas.create', $this->cliente))
        ->assertSuccessful()
        ->assertSee('Plano Prisma Pro')
        ->assertSee('data-valor="149.90"', false)
        ->assertSee('data-duracao="45"', false)
        ->assertSee('Plano já contratado')
        ->assertSee('já vigente')
        ->assertSee('value="'.$planoVigente->id.'"', false)
        ->assertSee('disabled', false)
        ->assertDontSee('Plano indisponível');

    expect($planoDisponivel->disponivel_para_venda)->toBeTrue();
});

it('contracts a plan and records its payment through the existing domain action', function () {
    $plano = Plano::factory()->create([
        'nome' => 'Plano Completo',
        'valor_padrao' => '99.90',
        'duracao_dias' => 30,
    ]);

    $response = $this->actingAs($this->administrador)
        ->post(route('admin.clientes.assinaturas.store', $this->cliente), [
            'plano_id' => $plano->id,
            'inicio_em' => '2026-07-18 08:30:00',
            'duracao_dias' => 60,
            'valor_pago' => '89.50',
            'data_pagamento' => '2026-07-17 11:45:00',
            'forma_pagamento' => 'pix',
            'observacao' => 'Condição comercial autorizada.',
        ]);

    $response
        ->assertRedirect(route('admin.clientes.show', $this->cliente))
        ->assertSessionHas(
            'sucesso',
            'Plano contratado e pagamento registrado com sucesso.',
        );

    $assinatura = Assinatura::query()->sole();
    $pagamento = Pagamento::query()->sole();

    expect($assinatura)
        ->user_id->toBe($this->cliente->id)
        ->plano_id->toBe($plano->id)
        ->nome_plano->toBe('Plano Completo')
        ->valor_referencia->toBe('99.90')
        ->duracao_dias->toBe(60)
        ->inicio_em->toDateTimeString()->toBe('2026-07-18 08:30:00')
        ->termino_em->toDateTimeString()->toBe('2026-09-16 08:30:00')
        ->observacao->toBe('Condição comercial autorizada.')
        ->and($pagamento)
        ->user_id->toBe($this->cliente->id)
        ->assinatura_id->toBe($assinatura->id)
        ->valor->toBe('89.50')
        ->tipo->toBe('contratacao')
        ->forma_pagamento->toBe('pix')
        ->registrado_por->toBe($this->administrador->id)
        ->data_pagamento->toDateTimeString()->toBe('2026-07-17 11:45:00');
});

it('does not change another current subscription when contracting a different plan', function () {
    $planoAtual = Plano::factory()->create();
    $planoNovo = Plano::factory()->create();
    $assinaturaAtual = Assinatura::factory()
        ->vigente()
        ->for($this->cliente, 'cliente')
        ->for($planoAtual)
        ->create();
    $camposPreservados = [
        'user_id',
        'plano_id',
        'nome_plano',
        'valor_referencia',
        'duracao_dias',
        'inicio_em',
        'termino_em',
        'cancelada_em',
        'observacao',
        'updated_at',
    ];
    $dadosOriginais = array_map(
        fn (string $campo): mixed => $assinaturaAtual->getRawOriginal($campo),
        $camposPreservados,
    );

    $this->actingAs($this->administrador)
        ->post(route('admin.clientes.assinaturas.store', $this->cliente), [
            'plano_id' => $planoNovo->id,
            'inicio_em' => '2026-07-17 12:00:00',
            'duracao_dias' => 30,
            'valor_pago' => '50.00',
            'data_pagamento' => '2026-07-17 12:00:00',
            'forma_pagamento' => 'transferencia',
            'observacao' => null,
        ])
        ->assertRedirect();

    $assinaturaPreservada = $assinaturaAtual->fresh();

    expect(Assinatura::query()->count())->toBe(2)
        ->and(array_map(
            fn (string $campo): mixed => $assinaturaPreservada->getRawOriginal($campo),
            $camposPreservados,
        ))
        ->toBe($dadosOriginais);
});

it('rejects a duplicate current plan without creating financial history', function () {
    $plano = Plano::factory()->create();
    Assinatura::factory()
        ->vigente()
        ->for($this->cliente, 'cliente')
        ->for($plano)
        ->create();

    $this->actingAs($this->administrador)
        ->from(route('admin.clientes.assinaturas.create', $this->cliente))
        ->post(route('admin.clientes.assinaturas.store', $this->cliente), [
            'plano_id' => $plano->id,
            'inicio_em' => '2026-07-17 12:00:00',
            'duracao_dias' => 30,
            'valor_pago' => '50.00',
            'data_pagamento' => '2026-07-17 12:00:00',
            'forma_pagamento' => 'pix',
        ])
        ->assertRedirect(route('admin.clientes.assinaturas.create', $this->cliente))
        ->assertSessionHasErrors('plano');

    expect(Assinatura::query()->count())->toBe(1)
        ->and(Pagamento::query()->count())->toBe(0);
});

it('validates fields and refuses plans unavailable for sale', function () {
    $plano = Plano::factory()->create([
        'disponivel_para_venda' => false,
    ]);

    $this->actingAs($this->administrador)
        ->post(route('admin.clientes.assinaturas.store', $this->cliente), [
            'plano_id' => $plano->id,
            'inicio_em' => 'invalid-date',
            'duracao_dias' => 0,
            'valor_pago' => '-1',
            'data_pagamento' => '',
            'forma_pagamento' => 'boleto',
            'observacao' => str_repeat('a', 2001),
        ])
        ->assertSessionHasErrors([
            'plano_id',
            'inicio_em',
            'duracao_dias',
            'valor_pago',
            'data_pagamento',
            'forma_pagamento',
            'observacao',
        ]);

    expect(Assinatura::query()->count())->toBe(0)
        ->and(Pagamento::query()->count())->toBe(0);
});

it('does not offer contracting for administrative users', function () {
    $analista = User::factory()->create(['perfil' => 'analista']);

    $this->actingAs($this->administrador)
        ->get(route('admin.clientes.assinaturas.create', $analista))
        ->assertNotFound();
});
