<?php

use App\Models\Assinatura;
use App\Models\Pagamento;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Date::setTestNow('2026-07-17 12:00:00');
    $this->administrador = User::factory()->create([
        'perfil' => 'administrador',
    ]);
    $this->analista = User::factory()->create([
        'perfil' => 'analista',
    ]);
});

afterEach(function () {
    Date::setTestNow();
});

it('protects reports by authentication and administrative profile without requiring a subscription', function () {
    $this->get(route('admin.relatorios.index'))
        ->assertRedirect(route('login'));

    $cliente = User::factory()->create();

    $this->actingAs($cliente)
        ->get(route('admin.relatorios.index'))
        ->assertForbidden();

    $this->actingAs($cliente)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertDontSee(route('admin.relatorios.index'), false);

    foreach ([$this->administrador, $this->analista] as $usuario) {
        $this->actingAs($usuario)
            ->get(route('admin.relatorios.index'))
            ->assertSuccessful()
            ->assertSee(route('admin.relatorios.index'), false);
    }

    $middlewares = collect(
        Route::getRoutes()
            ->getByName('admin.relatorios.index')
            ->gatherMiddleware(),
    );

    expect($middlewares)
        ->toContain('perfil:administrador,analista')
        ->and($middlewares->contains(
            fn (string $middleware) => Str::startsWith($middleware, 'assinatura:'),
        ))->toBeFalse();
});

it('renders the Prisma report catalog and every administrative filter', function () {
    $this->actingAs($this->administrador)
        ->get(route('admin.relatorios.index'))
        ->assertSuccessful()
        ->assertSee('Relatórios administrativos')
        ->assertSee('Clientes com acesso vigente')
        ->assertSee('Clientes sem acesso vigente')
        ->assertSee('Vencimentos em 7 dias')
        ->assertSee('Assinaturas vencidas')
        ->assertSee('Assinaturas canceladas')
        ->assertSee('Clientes com múltiplos planos')
        ->assertSee('Pagamentos no mês')
        ->assertSee('Receita por período')
        ->assertSee('Receita por plano')
        ->assertSee('Histórico de pagamentos')
        ->assertSee('name="cliente_id"', false)
        ->assertSee('name="plano_id"', false)
        ->assertSee('name="inicio"', false)
        ->assertSee('name="fim"', false)
        ->assertSee('name="forma_pagamento"', false)
        ->assertSee('name="tipo"', false)
        ->assertSee('name="registrado_por"', false)
        ->assertSee(
            route('admin.relatorios.index', [
                'relatorio' => 'clientes_vigentes',
            ]).'#resultado-relatorio',
            false,
        )
        ->assertSee(
            'action="'.route('admin.relatorios.index').'#resultado-relatorio"',
            false,
        )
        ->assertSee('id="resultado-relatorio"', false)
        ->assertSee('aria-labelledby="titulo-resultado-relatorio"', false)
        ->assertSee('admin-prisma-', false)
        ->assertSee('Dados por todos os ângulos.');
});

it('separates customers with and without current subscriptions using dates', function () {
    $plano = Plano::factory()->create();
    $clienteVigente = User::factory()->create(['name' => 'Cliente vigente']);
    $clienteSemVigencia = User::factory()->create(['name' => 'Cliente sem vigência']);
    Assinatura::factory()
        ->vigente()
        ->for($clienteVigente, 'cliente')
        ->for($plano)
        ->create();
    Assinatura::factory()
        ->vencida()
        ->for($clienteSemVigencia, 'cliente')
        ->for($plano)
        ->create();
    Assinatura::factory()
        ->futura()
        ->for($clienteSemVigencia, 'cliente')
        ->for($plano)
        ->create();
    Assinatura::factory()
        ->cancelada()
        ->for($clienteSemVigencia, 'cliente')
        ->for($plano)
        ->create();

    $vigentes = $this->actingAs($this->administrador)
        ->get(route('admin.relatorios.index', [
            'relatorio' => 'clientes_vigentes',
        ]))
        ->viewData('resultados');
    $semVigencia = $this->actingAs($this->administrador)
        ->get(route('admin.relatorios.index', [
            'relatorio' => 'clientes_sem_vigencia',
        ]))
        ->viewData('resultados');

    expect($vigentes->pluck('id')->all())
        ->toBe([$clienteVigente->id])
        ->and($semVigencia->pluck('id')->all())
        ->toContain($clienteSemVigencia->id)
        ->not->toContain($clienteVigente->id);
});

it('separates upcoming expired and canceled subscriptions', function () {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create();
    $proxima = Assinatura::factory()
        ->vigente()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create(['termino_em' => now()->addDays(3)]);
    $foraDaJanela = Assinatura::factory()
        ->vigente()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create(['termino_em' => now()->addDays(8)]);
    $vencida = Assinatura::factory()
        ->vencida()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create(['termino_em' => now()->subDay()]);
    $cancelada = Assinatura::factory()
        ->cancelada()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create([
            'termino_em' => now()->subDay(),
            'cancelada_em' => now()->subDays(2),
        ]);

    $proximas = $this->actingAs($this->administrador)
        ->get(route('admin.relatorios.index', [
            'relatorio' => 'vencimentos_7_dias',
        ]))
        ->viewData('resultados');
    $vencidas = $this->actingAs($this->administrador)
        ->get(route('admin.relatorios.index', [
            'relatorio' => 'assinaturas_vencidas',
        ]))
        ->viewData('resultados');
    $canceladas = $this->actingAs($this->administrador)
        ->get(route('admin.relatorios.index', [
            'relatorio' => 'assinaturas_canceladas',
        ]))
        ->viewData('resultados');

    expect($proximas->pluck('id')->all())
        ->toBe([$proxima->id])
        ->not->toContain($foraDaJanela->id)
        ->and($vencidas->pluck('id')->all())
        ->toContain($vencida->id)
        ->not->toContain($cancelada->id)
        ->and($canceladas->pluck('id')->all())
        ->toContain($cancelada->id);
});

it('finds customers with more than one distinct current plan', function () {
    $clienteMultiplo = User::factory()->create();
    $clientePlanoDuplicado = User::factory()->create();
    $planoA = Plano::factory()->create();
    $planoB = Plano::factory()->create();

    foreach ([$planoA, $planoB] as $plano) {
        Assinatura::factory()
            ->vigente()
            ->for($clienteMultiplo, 'cliente')
            ->for($plano)
            ->create();
    }

    Assinatura::factory()
        ->count(2)
        ->vigente()
        ->for($clientePlanoDuplicado, 'cliente')
        ->for($planoA)
        ->create();

    $resultados = $this->actingAs($this->administrador)
        ->get(route('admin.relatorios.index', [
            'relatorio' => 'clientes_multiplos_planos',
        ]))
        ->viewData('resultados');

    expect($resultados->pluck('id')->all())
        ->toBe([$clienteMultiplo->id])
        ->and((int) $resultados->first()->planos_vigentes_count)->toBe(2)
        ->and($resultados->first()->relationLoaded('assinaturas'))->toBeTrue()
        ->and($resultados->first()->assinaturas->every(
            fn (Assinatura $assinatura): bool => $assinatura->relationLoaded('plano'),
        ))->toBeTrue();
});

it('lists monthly payments and preserves archived customer and responsible data', function () {
    $cliente = User::factory()->create(['name' => 'Cliente financeiro']);
    $plano = Plano::factory()->create();
    $assinatura = Assinatura::factory()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create(['nome_plano' => 'Plano financeiro']);
    $pagamentoDoMes = Pagamento::factory()
        ->for($cliente, 'cliente')
        ->for($assinatura)
        ->for($this->analista, 'registradoPor')
        ->create([
            'data_pagamento' => '2026-07-10 09:00:00',
            'valor' => '149.90',
        ]);
    Pagamento::factory()
        ->for($cliente, 'cliente')
        ->for($assinatura)
        ->for($this->analista, 'registradoPor')
        ->create(['data_pagamento' => '2026-06-30 23:59:59']);
    $cliente->delete();
    $this->analista->delete();

    $response = $this->actingAs($this->administrador)
        ->get(route('admin.relatorios.index', [
            'relatorio' => 'pagamentos_mes',
        ]));
    $resultados = $response->viewData('resultados');

    $response
        ->assertSuccessful()
        ->assertSee('Cliente financeiro')
        ->assertSee('Plano financeiro')
        ->assertSee('R$ 149,90');

    expect($resultados->pluck('id')->all())
        ->toBe([$pagamentoDoMes->id])
        ->and($resultados->first()->cliente->trashed())->toBeTrue()
        ->and($resultados->first()->registradoPor->trashed())->toBeTrue();
});

it('filters payment history by every financial dimension', function () {
    $clienteAlvo = User::factory()->create();
    $outroCliente = User::factory()->create();
    $planoAlvo = Plano::factory()->create();
    $outroPlano = Plano::factory()->create();
    $assinaturaAlvo = Assinatura::factory()
        ->for($clienteAlvo, 'cliente')
        ->for($planoAlvo)
        ->create();
    $outraAssinatura = Assinatura::factory()
        ->for($outroCliente, 'cliente')
        ->for($outroPlano)
        ->create();
    $pagamentoAlvo = Pagamento::factory()
        ->for($clienteAlvo, 'cliente')
        ->for($assinaturaAlvo)
        ->for($this->analista, 'registradoPor')
        ->create([
            'data_pagamento' => '2026-07-11 10:00:00',
            'forma_pagamento' => 'pix',
            'tipo' => 'renovacao',
        ]);
    Pagamento::factory()
        ->for($outroCliente, 'cliente')
        ->for($outraAssinatura)
        ->for($this->administrador, 'registradoPor')
        ->create([
            'data_pagamento' => '2026-07-11 10:00:00',
            'forma_pagamento' => 'cartao',
            'tipo' => 'contratacao',
        ]);

    $resultados = $this->actingAs($this->administrador)
        ->get(route('admin.relatorios.index', [
            'relatorio' => 'historico_cliente',
            'cliente_id' => $clienteAlvo->id,
            'plano_id' => $planoAlvo->id,
            'inicio' => '2026-07-01',
            'fim' => '2026-07-31',
            'forma_pagamento' => 'pix',
            'tipo' => 'renovacao',
            'registrado_por' => $this->analista->id,
        ]))
        ->viewData('resultados');

    expect($resultados->pluck('id')->all())->toBe([$pagamentoAlvo->id]);
});

it('calculates registered revenue by period and financial filters', function () {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create();
    $assinatura = Assinatura::factory()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create();

    foreach ([
        ['valor' => '100.10', 'forma' => 'pix', 'tipo' => 'contratacao', 'data' => '2026-07-05 10:00:00'],
        ['valor' => '49.90', 'forma' => 'pix', 'tipo' => 'contratacao', 'data' => '2026-07-20 10:00:00'],
        ['valor' => '800.00', 'forma' => 'cartao', 'tipo' => 'renovacao', 'data' => '2026-07-10 10:00:00'],
        ['valor' => '999.00', 'forma' => 'pix', 'tipo' => 'contratacao', 'data' => '2026-08-01 10:00:00'],
    ] as $dados) {
        Pagamento::factory()
            ->for($cliente, 'cliente')
            ->for($assinatura)
            ->for($this->administrador, 'registradoPor')
            ->create([
                'valor' => $dados['valor'],
                'forma_pagamento' => $dados['forma'],
                'tipo' => $dados['tipo'],
                'data_pagamento' => $dados['data'],
            ]);
    }

    $response = $this->actingAs($this->administrador)
        ->get(route('admin.relatorios.index', [
            'relatorio' => 'receita_periodo',
            'inicio' => '2026-07-01',
            'fim' => '2026-07-31',
            'forma_pagamento' => 'pix',
            'tipo' => 'contratacao',
            'registrado_por' => $this->administrador->id,
        ]));
    $resultado = $response->viewData('resultados');

    $response
        ->assertSuccessful()
        ->assertSee('R$ 150,00');

    expect((float) $resultado->receita_total)->toBe(150.0)
        ->and((int) $resultado->pagamentos_count)->toBe(2);
});

it('groups registered revenue by the historical plan snapshot', function () {
    $cliente = User::factory()->create();
    $planoA = Plano::factory()->create();
    $planoB = Plano::factory()->create();
    $assinaturaA = Assinatura::factory()
        ->for($cliente, 'cliente')
        ->for($planoA)
        ->create(['nome_plano' => 'Plano Prisma A']);
    $assinaturaB = Assinatura::factory()
        ->for($cliente, 'cliente')
        ->for($planoB)
        ->create(['nome_plano' => 'Plano Prisma B']);

    foreach (['100.00', '50.00'] as $valor) {
        Pagamento::factory()
            ->for($cliente, 'cliente')
            ->for($assinaturaA)
            ->for($this->administrador, 'registradoPor')
            ->create([
                'valor' => $valor,
                'data_pagamento' => '2026-07-10 10:00:00',
            ]);
    }
    Pagamento::factory()
        ->for($cliente, 'cliente')
        ->for($assinaturaB)
        ->for($this->administrador, 'registradoPor')
        ->create([
            'valor' => '25.00',
            'data_pagamento' => '2026-07-10 10:00:00',
        ]);
    $planoA->update(['nome' => 'Plano renomeado']);

    $resultados = $this->actingAs($this->administrador)
        ->get(route('admin.relatorios.index', [
            'relatorio' => 'receita_plano',
        ]))
        ->viewData('resultados');
    $receitaPlanoA = $resultados->firstWhere('plano_id', $planoA->id);

    expect($receitaPlanoA)
        ->not->toBeNull()
        ->nome_plano->toBe('Plano Prisma A')
        ->and((float) $receitaPlanoA->receita_total)->toBe(150.0)
        ->and((int) $receitaPlanoA->pagamentos_count)->toBe(2);
});

it('validates report periods and allowed filter values', function () {
    $this->actingAs($this->administrador)
        ->get(route('admin.relatorios.index', [
            'relatorio' => 'inexistente',
            'inicio' => '2026-07-20',
            'fim' => '2026-07-01',
            'forma_pagamento' => 'boleto',
            'tipo' => 'estorno',
        ]))
        ->assertSessionHasErrors([
            'relatorio',
            'fim',
            'forma_pagamento',
            'tipo',
        ]);
});
