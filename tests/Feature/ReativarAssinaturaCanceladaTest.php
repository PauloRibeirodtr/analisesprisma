<?php

use App\Actions\Assinaturas\ReativarAssinaturaCancelada;
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

    $this->reativacao = app(ReativarAssinaturaCancelada::class);
    $this->cliente = User::factory()->create(['perfil' => 'cliente']);
    $this->plano = Plano::factory()->create(['slug' => 'cores']);
    $this->responsavel = User::factory()->create(['perfil' => 'administrador']);
    $this->assinatura = Assinatura::factory()
        ->for($this->cliente, 'cliente')
        ->for($this->plano)
        ->create([
            'duracao_dias' => 30,
            'inicio_em' => '2026-05-01 12:00:00',
            'termino_em' => '2026-05-31 12:00:00',
            'cancelada_em' => '2026-05-15 10:00:00',
            'observacao' => 'Assinatura cancelada a pedido do cliente.',
        ]);
    $this->pagamentoAnterior = Pagamento::factory()
        ->for($this->cliente, 'cliente')
        ->for($this->assinatura)
        ->for($this->responsavel, 'registradoPor')
        ->create([
            'tipo' => 'contratacao',
            'valor' => '100.00',
        ]);
    $this->dadosReativacao = [
        'assinatura' => $this->assinatura,
        'confirmada' => true,
        'inicioEm' => now()->addDay(),
        'duracaoDias' => 30,
        'valorRecebido' => '149.90',
        'dataPagamento' => now(),
        'formaPagamento' => 'pix',
        'observacao' => 'Cliente confirmou a reativação paga.',
        'responsavel' => $this->responsavel,
    ];
});

afterEach(function () {
    Date::setTestNow();
});

it('reactivates a canceled subscription with a payment', function () {
    $pagamentoAnteriorOriginal = $this->pagamentoAnterior->getRawOriginal();

    $resultado = $this->reativacao->executar(...$this->dadosReativacao);

    expect($resultado['assinatura'])
        ->inicio_em->toDateTimeString()->toBe('2026-07-18 12:00:00')
        ->termino_em->toDateTimeString()->toBe('2026-08-17 12:00:00')
        ->duracao_dias->toBe(30)
        ->cancelada_em->toBeNull()
        ->observacao->toContain('Assinatura cancelada a pedido do cliente.')
        ->observacao->toContain('Período anterior: 01/05/2026 12:00:00 até 31/05/2026 12:00:00')
        ->observacao->toContain('Cliente confirmou a reativação paga.')
        ->and($resultado['pagamento'])
        ->toBeInstanceOf(Pagamento::class)
        ->valor->toBe('149.90')
        ->tipo->toBe('renovacao')
        ->forma_pagamento->toBe('pix')
        ->observacao->toContain('Reativação em 17/07/2026 12:00:00')
        ->and($resultado['pagamento']->registradoPor->is($this->responsavel))
        ->toBeTrue()
        ->and($this->pagamentoAnterior->fresh()->getRawOriginal())
        ->toEqual($pagamentoAnteriorOriginal)
        ->and($this->assinatura->pagamentos()->count())->toBe(2);
});

it('reactivates as a courtesy without creating a payment', function () {
    $analista = User::factory()->create(['perfil' => 'analista']);
    $this->dadosReativacao = array_merge($this->dadosReativacao, [
        'valorRecebido' => null,
        'dataPagamento' => null,
        'formaPagamento' => null,
        'observacao' => 'Cortesia comercial aprovada para recuperação do cliente.',
        'responsavel' => $analista,
    ]);

    $resultado = $this->reativacao->executar(...$this->dadosReativacao);

    expect($resultado['pagamento'])->toBeNull()
        ->and($resultado['assinatura']->cancelada_em)->toBeNull()
        ->and($resultado['assinatura']->observacao)
        ->toContain('Cortesia comercial aprovada')
        ->and($this->assinatura->pagamentos()->count())->toBe(1);
});

it('requires explicit confirmation', function () {
    $this->dadosReativacao['confirmada'] = false;
    $valoresOriginais = $this->assinatura->getRawOriginal();

    expect(fn () => $this->reativacao->executar(...$this->dadosReativacao))
        ->toThrow(ValidationException::class);

    expect($this->assinatura->fresh()->getRawOriginal())->toEqual($valoresOriginais)
        ->and($this->assinatura->pagamentos()->count())->toBe(1);
});

it('requires a justification for a courtesy reactivation', function () {
    $this->dadosReativacao = array_merge($this->dadosReativacao, [
        'valorRecebido' => null,
        'dataPagamento' => null,
        'formaPagamento' => null,
        'observacao' => '',
    ]);

    expect(fn () => $this->reativacao->executar(...$this->dadosReativacao))
        ->toThrow(ValidationException::class);

    expect($this->assinatura->fresh()->cancelada_em)->not->toBeNull()
        ->and($this->assinatura->pagamentos()->count())->toBe(1);
});

it('rejects an incomplete paid reactivation', function () {
    $this->dadosReativacao['dataPagamento'] = null;

    expect(fn () => $this->reativacao->executar(...$this->dadosReativacao))
        ->toThrow(ValidationException::class);

    expect($this->assinatura->fresh()->cancelada_em)->not->toBeNull()
        ->and($this->assinatura->pagamentos()->count())->toBe(1);
});

it('rejects an active subscription', function () {
    $this->assinatura->update(['cancelada_em' => null]);

    expect(fn () => $this->reativacao->executar(...$this->dadosReativacao))
        ->toThrow(ValidationException::class);

    expect($this->assinatura->pagamentos()->count())->toBe(1);
});

it('rejects a responsible user without an administrative profile', function () {
    $this->dadosReativacao['responsavel'] = User::factory()->create([
        'perfil' => 'cliente',
    ]);

    expect(fn () => $this->reativacao->executar(...$this->dadosReativacao))
        ->toThrow(ValidationException::class);

    expect($this->assinatura->fresh()->cancelada_em)->not->toBeNull()
        ->and($this->assinatura->pagamentos()->count())->toBe(1);
});

it('rolls back every change when payment creation fails', function () {
    $valoresOriginais = $this->assinatura->getRawOriginal();

    Event::listen(
        'eloquent.creating: '.Pagamento::class,
        fn () => throw new RuntimeException('Falha simulada no pagamento.'),
    );

    expect(fn () => $this->reativacao->executar(...$this->dadosReativacao))
        ->toThrow(RuntimeException::class, 'Falha simulada no pagamento.');

    expect($this->assinatura->fresh()->getRawOriginal())->toEqual($valoresOriginais)
        ->and($this->assinatura->pagamentos()->count())->toBe(1);
});
