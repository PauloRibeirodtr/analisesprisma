<?php

use App\Actions\Assinaturas\ContratarPlanoManualmente;
use App\Models\Assinatura;
use App\Models\Pagamento;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Date::setTestNow('2026-07-17 12:00:00');

    $this->contratacao = app(ContratarPlanoManualmente::class);
    $this->cliente = User::factory()->create(['perfil' => 'cliente']);
    $this->plano = Plano::factory()->create([
        'nome' => 'Análise de Cores',
        'slug' => 'cores',
        'valor_padrao' => '149.90',
        'disponivel_para_venda' => true,
    ]);
    $this->responsavel = User::factory()->create(['perfil' => 'analista']);
    $this->dadosContratacao = [
        'cliente' => $this->cliente,
        'plano' => $this->plano,
        'inicioEm' => now(),
        'duracaoDias' => 30,
        'valorPago' => '139.90',
        'dataPagamento' => now(),
        'formaPagamento' => 'pix',
        'observacao' => 'Contratação registrada manualmente.',
        'responsavel' => $this->responsavel,
    ];
});

afterEach(function () {
    Date::setTestNow();
});

it('creates the first subscription and its contracting payment', function () {
    $resultado = $this->contratacao->executar(...$this->dadosContratacao);

    $assinatura = $resultado['assinatura'];
    $pagamento = $resultado['pagamento'];

    expect($assinatura)
        ->toBeInstanceOf(Assinatura::class)
        ->nome_plano->toBe('Análise de Cores')
        ->valor_referencia->toBe('149.90')
        ->duracao_dias->toBe(30)
        ->inicio_em->toDateTimeString()->toBe('2026-07-17 12:00:00')
        ->termino_em->toDateTimeString()->toBe('2026-08-16 12:00:00')
        ->and($assinatura->cliente->is($this->cliente))->toBeTrue()
        ->and($assinatura->plano->is($this->plano))->toBeTrue()
        ->and($pagamento)
        ->toBeInstanceOf(Pagamento::class)
        ->valor->toBe('139.90')
        ->tipo->toBe('contratacao')
        ->forma_pagamento->toBe('pix')
        ->data_pagamento->toDateTimeString()->toBe('2026-07-17 12:00:00')
        ->and($pagamento->assinatura->is($assinatura))->toBeTrue()
        ->and($pagamento->cliente->is($this->cliente))->toBeTrue()
        ->and($pagamento->registradoPor->is($this->responsavel))->toBeTrue();
});

it('creates different plans without changing an existing subscription', function () {
    $primeiroResultado = $this->contratacao->executar(...$this->dadosContratacao);
    $valoresOriginais = $primeiroResultado['assinatura']->getRawOriginal();
    $outroPlano = Plano::factory()->create([
        'nome' => 'Branco 14x',
        'slug' => 'branco-14x',
    ]);

    $segundoResultado = $this->contratacao->executar(...array_merge(
        $this->dadosContratacao,
        ['plano' => $outroPlano],
    ));

    expect($this->cliente->assinaturas()->count())->toBe(2)
        ->and($this->cliente->pagamentos()->count())->toBe(2)
        ->and($segundoResultado['assinatura']->plano->is($outroPlano))->toBeTrue()
        ->and($primeiroResultado['assinatura']->fresh()->getRawOriginal())
        ->toEqual($valoresOriginais);
});

it('rejects another current subscription for the same plan', function () {
    $this->contratacao->executar(...$this->dadosContratacao);

    try {
        $this->contratacao->executar(...$this->dadosContratacao);
        $this->fail('A segunda contratação deveria ter sido rejeitada.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['plano'])->toBe([
            'O cliente já possui acesso vigente a este plano. Utilize a operação de renovação.',
        ]);
    }

    expect($this->cliente->assinaturas()->count())->toBe(1)
        ->and($this->cliente->pagamentos()->count())->toBe(1);
});

it('allows contracting after an older subscription has expired', function () {
    Assinatura::factory()
        ->vencida()
        ->for($this->cliente, 'cliente')
        ->for($this->plano)
        ->create();

    $resultado = $this->contratacao->executar(...$this->dadosContratacao);

    expect($this->cliente->assinaturas()->count())->toBe(2)
        ->and($resultado['assinatura']->estaVigente())->toBeTrue();
});

it('rejects a plan unavailable for sale', function () {
    $this->plano->update(['disponivel_para_venda' => false]);

    expect(fn () => $this->contratacao->executar(...$this->dadosContratacao))
        ->toThrow(ValidationException::class);

    expect(Assinatura::query()->count())->toBe(0)
        ->and(Pagamento::query()->count())->toBe(0);
});

it('rejects a responsible user without an administrative profile', function () {
    $this->dadosContratacao['responsavel'] = User::factory()->create([
        'perfil' => 'cliente',
    ]);

    expect(fn () => $this->contratacao->executar(...$this->dadosContratacao))
        ->toThrow(ValidationException::class);

    expect(Assinatura::query()->count())->toBe(0)
        ->and(Pagamento::query()->count())->toBe(0);
});

it('rejects invalid payment data before creating records', function () {
    $this->dadosContratacao['valorPago'] = '-1.00';

    expect(fn () => $this->contratacao->executar(...$this->dadosContratacao))
        ->toThrow(ValidationException::class);

    expect(Assinatura::query()->count())->toBe(0)
        ->and(Pagamento::query()->count())->toBe(0);
});

it('rolls back the subscription when payment creation fails', function () {
    Event::listen(
        'eloquent.creating: '.Pagamento::class,
        fn () => throw new RuntimeException('Falha simulada no pagamento.'),
    );

    expect(fn () => $this->contratacao->executar(...$this->dadosContratacao))
        ->toThrow(RuntimeException::class, 'Falha simulada no pagamento.');

    expect(Assinatura::query()->count())->toBe(0)
        ->and(Pagamento::query()->count())->toBe(0);
});
