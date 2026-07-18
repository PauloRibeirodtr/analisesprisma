<?php

use App\Models\Assinatura;
use App\Models\Pagamento;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('casts values and exposes its relationships', function () {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create();
    $assinatura = Assinatura::factory()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create();
    $responsavel = User::factory()->create(['perfil' => 'analista']);

    $pagamento = Pagamento::factory()
        ->for($cliente, 'cliente')
        ->for($assinatura)
        ->for($responsavel, 'registradoPor')
        ->create([
            'valor' => 149.9,
            'data_pagamento' => now(),
            'dados_gateway' => ['origem' => 'manual'],
        ]);

    expect($pagamento)
        ->valor->toBe('149.90')
        ->data_pagamento->toBeInstanceOf(DateTimeInterface::class)
        ->dados_gateway->toBe(['origem' => 'manual'])
        ->and($pagamento->cliente->is($cliente))->toBeTrue()
        ->and($pagamento->assinatura->is($assinatura))->toBeTrue()
        ->and($pagamento->registradoPor->is($responsavel))->toBeTrue();
});

it('defines the allowed payment types and methods', function () {
    expect(Pagamento::TIPOS)->toBe([
        'contratacao',
        'renovacao',
        'ajuste',
    ])->and(Pagamento::FORMAS_PAGAMENTO)->toBe([
        'pix',
        'dinheiro',
        'transferencia',
        'cartao',
        'outro',
    ]);
});

it('keeps gateway fields optional', function () {
    $pagamento = Pagamento::factory()->create();

    expect($pagamento)
        ->gateway->toBeNull()
        ->gateway_payment_id->toBeNull()
        ->dados_gateway->toBeNull();
});

it('prevents physical deletion of a subscription with payments', function () {
    $pagamento = Pagamento::factory()->create();

    expect(fn () => $pagamento->assinatura->delete())
        ->toThrow(QueryException::class);
});

it('prevents physical deletion of the user who registered a payment', function () {
    $pagamento = Pagamento::factory()->create();

    expect(fn () => $pagamento->registradoPor->forceDelete())
        ->toThrow(QueryException::class);
});
