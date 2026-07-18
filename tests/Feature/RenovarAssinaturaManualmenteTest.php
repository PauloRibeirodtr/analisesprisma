<?php

use App\Actions\Assinaturas\RenovarAssinaturaManualmente;
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

    $this->renovacao = app(RenovarAssinaturaManualmente::class);
    $this->cliente = User::factory()->create(['perfil' => 'cliente']);
    $this->plano = Plano::factory()->create(['slug' => 'cores']);
    $this->responsavel = User::factory()->create(['perfil' => 'analista']);
    $this->assinatura = Assinatura::factory()
        ->for($this->cliente, 'cliente')
        ->for($this->plano)
        ->create([
            'duracao_dias' => 30,
            'inicio_em' => '2026-07-01 12:00:00',
            'termino_em' => '2026-07-31 12:00:00',
            'cancelada_em' => null,
            'observacao' => 'Contratação inicial.',
        ]);
    $this->pagamentoAnterior = Pagamento::factory()
        ->for($this->cliente, 'cliente')
        ->for($this->assinatura)
        ->for($this->responsavel, 'registradoPor')
        ->create([
            'valor' => '100.00',
            'tipo' => 'contratacao',
            'observacao' => 'Pagamento original.',
        ]);
    $this->dadosRenovacao = [
        'assinatura' => $this->assinatura,
        'diasAdicionados' => 30,
        'valorRecebido' => '129.90',
        'dataPagamento' => now(),
        'formaPagamento' => 'pix',
        'observacao' => 'Renovação por mais 30 dias.',
        'responsavel' => $this->responsavel,
        'reativacaoEm' => null,
    ];
});

afterEach(function () {
    Date::setTestNow();
});

it('adds days after the current ending and creates a renewal payment', function () {
    $pagamentoAnteriorOriginal = $this->pagamentoAnterior->getRawOriginal();

    $resultado = $this->renovacao->executar(...$this->dadosRenovacao);

    expect($resultado['assinatura'])
        ->inicio_em->toDateTimeString()->toBe('2026-07-01 12:00:00')
        ->termino_em->toDateTimeString()->toBe('2026-08-30 12:00:00')
        ->duracao_dias->toBe(30)
        ->observacao->toBe('Contratação inicial.')
        ->and($resultado['pagamento'])
        ->valor->toBe('129.90')
        ->tipo->toBe('renovacao')
        ->forma_pagamento->toBe('pix')
        ->observacao->toBe('Renovação por mais 30 dias.')
        ->and($resultado['pagamento']->registradoPor->is($this->responsavel))
        ->toBeTrue()
        ->and($this->pagamentoAnterior->fresh()->getRawOriginal())
        ->toEqual($pagamentoAnteriorOriginal)
        ->and($this->assinatura->pagamentos()->count())->toBe(2);
});

it('renews from the current ending at its exact expiration instant', function () {
    $this->assinatura->update([
        'termino_em' => now(),
    ]);
    $this->dadosRenovacao['diasAdicionados'] = 10;

    $resultado = $this->renovacao->executar(...$this->dadosRenovacao);

    expect($resultado['assinatura'])
        ->inicio_em->toDateTimeString()->toBe('2026-07-01 12:00:00')
        ->termino_em->toDateTimeString()->toBe('2026-07-27 12:00:00');
});

it('restarts an expired subscription from the current time by default', function () {
    $this->assinatura->update([
        'inicio_em' => '2026-06-01 12:00:00',
        'termino_em' => '2026-07-16 12:00:00',
    ]);
    $this->dadosRenovacao['diasAdicionados'] = 10;

    $resultado = $this->renovacao->executar(...$this->dadosRenovacao);

    expect($resultado['assinatura'])
        ->inicio_em->toDateTimeString()->toBe('2026-07-17 12:00:00')
        ->termino_em->toDateTimeString()->toBe('2026-07-27 12:00:00');
});

it('uses the provided reactivation date for an expired subscription', function () {
    $this->assinatura->update([
        'inicio_em' => '2026-06-01 12:00:00',
        'termino_em' => '2026-07-16 12:00:00',
    ]);
    $this->dadosRenovacao['diasAdicionados'] = 15;
    $this->dadosRenovacao['reativacaoEm'] = now()->addDays(5);

    $resultado = $this->renovacao->executar(...$this->dadosRenovacao);

    expect($resultado['assinatura'])
        ->inicio_em->toDateTimeString()->toBe('2026-07-22 12:00:00')
        ->termino_em->toDateTimeString()->toBe('2026-08-06 12:00:00');
});

it('rejects renewal of a canceled subscription', function () {
    $this->assinatura->update(['cancelada_em' => now()]);
    $valoresOriginais = $this->assinatura->fresh()->getRawOriginal();

    expect(fn () => $this->renovacao->executar(...$this->dadosRenovacao))
        ->toThrow(ValidationException::class);

    expect($this->assinatura->fresh()->getRawOriginal())->toEqual($valoresOriginais)
        ->and($this->assinatura->pagamentos()->count())->toBe(1);
});

it('rejects renewal of a subscription that has not started', function () {
    $this->assinatura->update([
        'inicio_em' => now()->addDay(),
        'termino_em' => now()->addDays(31),
    ]);

    expect(fn () => $this->renovacao->executar(...$this->dadosRenovacao))
        ->toThrow(ValidationException::class);

    expect($this->assinatura->pagamentos()->count())->toBe(1);
});

it('rejects a responsible user without an administrative profile', function () {
    $this->dadosRenovacao['responsavel'] = User::factory()->create([
        'perfil' => 'cliente',
    ]);

    expect(fn () => $this->renovacao->executar(...$this->dadosRenovacao))
        ->toThrow(ValidationException::class);

    expect($this->assinatura->pagamentos()->count())->toBe(1);
});

it('does not interfere with another subscription from the customer', function () {
    $outraAssinatura = Assinatura::factory()
        ->for($this->cliente, 'cliente')
        ->create();
    $valoresOriginais = $outraAssinatura->getRawOriginal();

    $this->renovacao->executar(...$this->dadosRenovacao);

    expect($outraAssinatura->fresh()->getRawOriginal())
        ->toEqual($valoresOriginais);
});

it('rolls back date changes when payment creation fails', function () {
    $valoresOriginais = $this->assinatura->getRawOriginal();

    Event::listen(
        'eloquent.creating: '.Pagamento::class,
        fn () => throw new RuntimeException('Falha simulada no pagamento.'),
    );

    expect(fn () => $this->renovacao->executar(...$this->dadosRenovacao))
        ->toThrow(RuntimeException::class, 'Falha simulada no pagamento.');

    expect($this->assinatura->fresh()->getRawOriginal())->toEqual($valoresOriginais)
        ->and($this->assinatura->pagamentos()->count())->toBe(1);
});
