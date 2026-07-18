<?php

use App\Models\Assinatura;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Date;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Date::setTestNow('2026-07-17 12:00:00');
});

afterEach(function () {
    Date::setTestNow();
});

it('identifies a current subscription', function () {
    $assinatura = Assinatura::factory()->vigente()->create();

    expect($assinatura)
        ->estaVigente()->toBeTrue()
        ->estaVencida()->toBeFalse()
        ->estaCancelada()->toBeFalse()
        ->and(Assinatura::vigentes()->sole()->is($assinatura))->toBeTrue();
});

it('identifies a future subscription', function () {
    $assinatura = Assinatura::factory()->futura()->create();

    expect($assinatura->estaVigente())->toBeFalse()
        ->and(Assinatura::futuras()->sole()->is($assinatura))->toBeTrue()
        ->and(Assinatura::vigentes()->doesntExist())->toBeTrue();
});

it('identifies an expired subscription', function () {
    $assinatura = Assinatura::factory()->vencida()->create();

    expect($assinatura)
        ->estaVigente()->toBeFalse()
        ->estaVencida()->toBeTrue()
        ->and(Assinatura::vencidas()->sole()->is($assinatura))->toBeTrue();
});

it('identifies a canceled and expired subscription independently', function () {
    $assinatura = Assinatura::factory()->vencida()->cancelada()->create();

    expect($assinatura)
        ->estaVigente()->toBeFalse()
        ->estaVencida()->toBeTrue()
        ->estaCancelada()->toBeTrue()
        ->and(Assinatura::canceladas()->sole()->is($assinatura))->toBeTrue();
});

it('does not consider a canceled subscription current during its period', function () {
    $assinatura = Assinatura::factory()->vigente()->cancelada()->create();

    expect($assinatura)
        ->estaVigente()->toBeFalse()
        ->estaVencida()->toBeFalse()
        ->estaCancelada()->toBeTrue()
        ->and(Assinatura::vigentes()->doesntExist())->toBeTrue();
});

it('includes the exact start and end boundaries as current', function () {
    $inicioExato = Assinatura::factory()->create([
        'inicio_em' => now(),
        'termino_em' => now()->addDay(),
    ]);
    $terminoExato = Assinatura::factory()->create([
        'inicio_em' => now()->subDay(),
        'termino_em' => now(),
    ]);

    expect($inicioExato->estaVigente())->toBeTrue()
        ->and($terminoExato->estaVigente())->toBeTrue()
        ->and(Assinatura::vigentes()->pluck('id')->all())
        ->toEqualCanonicalizing([$inicioExato->id, $terminoExato->id]);
});

it('casts persisted values and exposes its relationships', function () {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create();
    $assinatura = Assinatura::factory()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create([
            'valor_referencia' => 49.9,
            'duracao_dias' => 45,
        ]);

    expect($assinatura)
        ->valor_referencia->toBe('49.90')
        ->duracao_dias->toBe(45)
        ->inicio_em->toBeInstanceOf(DateTimeInterface::class)
        ->termino_em->toBeInstanceOf(DateTimeInterface::class)
        ->and($assinatura->cliente->is($cliente))->toBeTrue()
        ->and($assinatura->plano->is($plano))->toBeTrue();
});
