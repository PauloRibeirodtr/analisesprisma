<?php

use App\Models\Plano;
use Database\Seeders\PlanoSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('uses the expected defaults for a new plan', function () {
    $plano = new Plano;

    expect($plano)
        ->duracao_dias->toBe(30)
        ->disponivel_para_venda->toBeTrue();
});

it('casts financial and availability fields', function () {
    $plano = Plano::factory()->create([
        'valor_padrao' => 19.9,
        'duracao_dias' => 45,
        'disponivel_para_venda' => false,
    ]);

    expect($plano)
        ->valor_padrao->toBe('19.90')
        ->duracao_dias->toBe(45)
        ->disponivel_para_venda->toBeFalse();
});

it('requires a unique slug', function () {
    Plano::factory()->create(['slug' => 'cores']);

    expect(fn () => Plano::factory()->create(['slug' => 'cores']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('soft deletes plans', function () {
    $plano = Plano::factory()->create();

    $plano->delete();

    $this->assertSoftDeleted($plano);
    expect(Plano::withTrashed()->find($plano->id))->not->toBeNull();
});

it('creates and restores the universal trial plan idempotently', function () {
    $this->seed(PlanoSeeder::class);

    $plano = Plano::query()
        ->where('slug', Plano::SLUG_TESTE)
        ->firstOrFail();
    $plano->delete();

    $this->seed(PlanoSeeder::class);

    $planoRestaurado = Plano::query()
        ->where('slug', Plano::SLUG_TESTE)
        ->firstOrFail();

    expect($planoRestaurado)
        ->nome->toBe('Teste gratuito')
        ->valor_padrao->toBe('0.00')
        ->duracao_dias->toBe(1)
        ->disponivel_para_venda->toBeFalse()
        ->and(Plano::withTrashed()->where('slug', Plano::SLUG_TESTE)->count())->toBe(1);
});
