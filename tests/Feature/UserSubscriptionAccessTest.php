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
});

afterEach(function () {
    Date::setTestNow();
});

it('grants access to a customer with the requested current plan', function () {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create(['slug' => 'cores']);
    Assinatura::factory()
        ->vigente()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create();

    expect($cliente->possuiPlanoVigente('cores'))->toBeTrue();
});

it('denies access when the requested plan is expired', function () {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create(['slug' => 'cores']);
    Assinatura::factory()
        ->vencida()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create();

    expect($cliente->possuiPlanoVigente('cores'))->toBeFalse();
});

it('denies access when the requested plan has not started', function () {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create(['slug' => 'cores']);
    Assinatura::factory()
        ->futura()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create();

    expect($cliente->possuiPlanoVigente('cores'))->toBeFalse();
});

it('denies access when the requested plan is canceled', function () {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create(['slug' => 'cores']);
    Assinatura::factory()
        ->vigente()
        ->cancelada()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create();

    expect($cliente->possuiPlanoVigente('cores'))->toBeFalse();
});

it('checks each plan independently for customers with multiple plans', function () {
    $cliente = User::factory()->create();

    foreach (['cores', 'branco-14x'] as $slug) {
        $plano = Plano::factory()->create(['slug' => $slug]);
        Assinatura::factory()
            ->vigente()
            ->for($cliente, 'cliente')
            ->for($plano)
            ->create();
    }

    expect($cliente->possuiPlanoVigente('cores'))->toBeTrue()
        ->and($cliente->possuiPlanoVigente('branco-14x'))->toBeTrue()
        ->and($cliente->possuiPlanoVigente('nao-contratado'))->toBeFalse();
});

it('grants every customer module through a current universal trial', function () {
    $cliente = User::factory()->create();
    $planoTeste = Plano::factory()->create([
        'slug' => Plano::SLUG_TESTE,
        'disponivel_para_venda' => false,
    ]);
    Assinatura::factory()
        ->vigente()
        ->for($cliente, 'cliente')
        ->for($planoTeste)
        ->create();

    expect($cliente->possuiPlanoVigente('cores'))->toBeTrue()
        ->and($cliente->possuiPlanoVigente('qualquer-modulo-futuro'))->toBeTrue();
});

it('does not grant universal access through an expired trial', function () {
    $cliente = User::factory()->create();
    $planoTeste = Plano::factory()->create(['slug' => Plano::SLUG_TESTE]);
    Assinatura::factory()
        ->vencida()
        ->for($cliente, 'cliente')
        ->for($planoTeste)
        ->create();

    expect($cliente->possuiPlanoVigente('cores'))->toBeFalse();
});

it('grants unrestricted access to administrative profiles', function (string $perfil) {
    $usuario = User::factory()->create(['perfil' => $perfil]);

    expect($usuario->possuiPlanoVigente('qualquer-modulo'))->toBeTrue();
})->with([
    'administrator' => 'administrador',
    'analyst' => 'analista',
]);

it('denies access to a customer without subscriptions', function () {
    $cliente = User::factory()->create();

    expect($cliente->possuiPlanoVigente('cores'))->toBeFalse();
});

it('keeps current access and history when a subscribed plan is soft deleted', function () {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create(['slug' => 'cores']);
    $assinatura = Assinatura::factory()
        ->vigente()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create();
    $plano->delete();

    expect($assinatura->fresh()->plano->is($plano))->toBeTrue()
        ->and($cliente->possuiPlanoVigente('cores'))->toBeTrue();
});

it('exposes customer and administrative financial relationships', function () {
    $cliente = User::factory()->create();
    $responsavel = User::factory()->create(['perfil' => 'analista']);
    $assinatura = Assinatura::factory()
        ->for($cliente, 'cliente')
        ->create();
    $pagamento = Pagamento::factory()
        ->for($cliente, 'cliente')
        ->for($assinatura)
        ->for($responsavel, 'registradoPor')
        ->create();

    expect($cliente->assinaturas->sole()->is($assinatura))->toBeTrue()
        ->and($cliente->pagamentos->sole()->is($pagamento))->toBeTrue()
        ->and($responsavel->pagamentosRegistrados->sole()->is($pagamento))->toBeTrue();
});
