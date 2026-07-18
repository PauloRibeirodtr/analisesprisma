<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\Pagamento;
use App\Models\Plano;
use App\Models\User;
use Database\Seeders\PlanoSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Date;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Date::setTestNow('2026-07-17 12:00:00');
    $this->seed(PlanoSeeder::class);
});

afterEach(function () {
    Date::setTestNow();
});

it('grants a 24-hour universal trial through public registration', function () {
    $this->post(route('register.store'), [
        'name' => 'Cliente Teste',
        'email' => 'cliente@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertRedirect('/dashboard');

    $cliente = User::query()
        ->where('email', 'cliente@example.com')
        ->firstOrFail();
    $assinatura = $cliente->assinaturas()->with('plano')->sole();

    expect($cliente->perfil)->toBe('cliente')
        ->and($assinatura->plano->slug)->toBe(Plano::SLUG_TESTE)
        ->and($assinatura->nome_plano)->toBe('Teste gratuito')
        ->and($assinatura->valor_referencia)->toBe('0.00')
        ->and($assinatura->duracao_dias)->toBe(1)
        ->and($assinatura->inicio_em->toDateTimeString())->toBe('2026-07-17 12:00:00')
        ->and($assinatura->termino_em->toDateTimeString())->toBe('2026-07-18 12:00:00')
        ->and($assinatura->cancelada_em)->toBeNull()
        ->and($cliente->possuiPlanoVigente('qualquer-modulo-cliente'))->toBeTrue()
        ->and(Pagamento::query()->count())->toBe(0);
});

it('uses the configured trial duration and exact expiration time', function () {
    config(['assinaturas.teste_gratuito_horas' => 72]);

    $cliente = app(CreateNewUser::class)->create([
        'name' => 'Cliente 72 Horas',
        'email' => '72horas@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);
    $assinatura = $cliente->assinaturas()->sole();

    expect($assinatura->duracao_dias)->toBe(3)
        ->and($assinatura->termino_em->toDateTimeString())->toBe('2026-07-20 12:00:00')
        ->and($cliente->possuiPlanoVigente('cores'))->toBeTrue();

    Date::setTestNow('2026-07-20 12:00:01');

    expect($cliente->possuiPlanoVigente('cores'))->toBeFalse();
});

it('grants the trial even though its plan is unavailable for sale', function () {
    $planoTeste = Plano::query()
        ->where('slug', Plano::SLUG_TESTE)
        ->sole();

    expect($planoTeste->disponivel_para_venda)->toBeFalse();

    $cliente = app(CreateNewUser::class)->create([
        'name' => 'Cliente Gratuito',
        'email' => 'gratuito@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    expect($cliente->assinaturas()->count())->toBe(1);
});

it('rolls back registration when the trial plan is unavailable', function () {
    Plano::query()
        ->where('slug', Plano::SLUG_TESTE)
        ->firstOrFail()
        ->delete();

    expect(fn () => app(CreateNewUser::class)->create([
        'name' => 'Cliente Sem Plano',
        'email' => 'semplano@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]))->toThrow(ModelNotFoundException::class);

    expect(User::query()->where('email', 'semplano@example.com')->exists())->toBeFalse();
});

it('rolls back registration when the configured duration is invalid', function () {
    config(['assinaturas.teste_gratuito_horas' => 0]);

    expect(fn () => app(CreateNewUser::class)->create([
        'name' => 'Cliente Configuração Inválida',
        'email' => 'configuracao@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]))->toThrow(InvalidArgumentException::class);

    expect(User::query()->where('email', 'configuracao@example.com')->exists())->toBeFalse();
});
