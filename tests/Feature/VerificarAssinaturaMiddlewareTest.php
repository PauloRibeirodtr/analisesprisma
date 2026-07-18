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
    User::query()->count();

    Route::middleware(['web', 'assinatura:cores'])
        ->get('/teste/middleware-assinatura', fn () => response()->json([
            'permitido' => true,
        ]))
        ->name('teste.middleware-assinatura');
});

afterEach(function () {
    Date::setTestNow();
});

it('redirects an unauthenticated web request to login', function () {
    $this->get('/teste/middleware-assinatura')
        ->assertRedirect(route('login'));
});

it('returns unauthorized for an unauthenticated json request', function () {
    $this->getJson('/teste/middleware-assinatura')
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
});

it('allows a customer with the required current plan', function () {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create(['slug' => 'cores']);
    Assinatura::factory()
        ->vigente()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create();

    $this->actingAs($cliente)
        ->getJson('/teste/middleware-assinatura')
        ->assertSuccessful()
        ->assertExactJson(['permitido' => true]);
});

it('allows a customer with a current universal trial', function () {
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

    $this->actingAs($cliente)
        ->getJson('/teste/middleware-assinatura')
        ->assertSuccessful();
});

it('allows administrative profiles without subscriptions', function (string $perfil) {
    $usuario = User::factory()->create(['perfil' => $perfil]);

    $this->actingAs($usuario)
        ->getJson('/teste/middleware-assinatura')
        ->assertSuccessful();
})->with([
    'administrator' => 'administrador',
    'analyst' => 'analista',
]);

it('redirects a customer without access to the informative page', function () {
    $cliente = User::factory()->create();

    $this->actingAs($cliente)
        ->get('/teste/middleware-assinatura')
        ->assertRedirect(route('assinatura.indisponivel'));

    $this->actingAs($cliente)
        ->get(route('assinatura.indisponivel'))
        ->assertSuccessful()
        ->assertSee('Seu acesso a este módulo não está disponível ou o período contratado terminou.')
        ->assertSee('Entre em contato com o suporte ou com seu analista responsável')
        ->assertSee(route('dashboard'), false)
        ->assertDontSee('cores');
});

it('shows the requested module and most recent ending without financial details', function () {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create([
        'nome' => 'Análises de Cores',
        'slug' => 'cores',
    ]);
    Assinatura::factory()
        ->vencida()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create([
            'termino_em' => '2026-06-30 09:00:00',
        ]);
    $assinaturaRecente = Assinatura::factory()
        ->vencida()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create([
            'termino_em' => '2026-07-16 18:45:00',
        ]);
    Pagamento::factory()
        ->for($cliente, 'cliente')
        ->for($assinaturaRecente)
        ->create([
            'valor' => '9876.54',
            'observacao' => 'Informação financeira confidencial.',
        ]);
    $plano->delete();

    $this->actingAs($cliente)
        ->get('/teste/middleware-assinatura')
        ->assertRedirect(route('assinatura.indisponivel'));

    $this->actingAs($cliente)
        ->get(route('assinatura.indisponivel'))
        ->assertSuccessful()
        ->assertSee('Análises de Cores')
        ->assertSee('Encerrado em 16/07/2026 18:45')
        ->assertDontSee('9876')
        ->assertDontSee('Informação financeira confidencial');
});

it('uses the universal trial ending while naming the requested module', function () {
    $cliente = User::factory()->create();
    Plano::factory()->create([
        'nome' => 'Módulo Cores Prisma',
        'slug' => 'cores',
    ]);
    $planoTeste = Plano::factory()->create([
        'nome' => 'Teste gratuito',
        'slug' => Plano::SLUG_TESTE,
        'disponivel_para_venda' => false,
    ]);
    Assinatura::factory()
        ->vencida()
        ->for($cliente, 'cliente')
        ->for($planoTeste)
        ->create([
            'termino_em' => '2026-07-17 08:30:00',
        ]);

    $this->actingAs($cliente)
        ->get('/teste/middleware-assinatura')
        ->assertRedirect(route('assinatura.indisponivel'));

    $this->actingAs($cliente)
        ->get(route('assinatura.indisponivel'))
        ->assertSuccessful()
        ->assertSee('Módulo Cores Prisma')
        ->assertSee('Encerrado em 17/07/2026 08:30');
});

it('renders safely without module context on direct access', function () {
    $cliente = User::factory()->create();

    $this->actingAs($cliente)
        ->get(route('assinatura.indisponivel'))
        ->assertSuccessful()
        ->assertSee('Seu acesso precisa ser renovado')
        ->assertDontSee('Módulo solicitado')
        ->assertDontSee('Último período de acesso');
});

it('returns forbidden without sensitive details for a json request', function () {
    $cliente = User::factory()->create();

    $this->actingAs($cliente)
        ->getJson('/teste/middleware-assinatura')
        ->assertForbidden()
        ->assertSessionMissing('modulo_solicitado')
        ->assertExactJson([
            'message' => 'Seu acesso a este módulo não está disponível ou o período contratado terminou.',
        ]);
});

it('blocks subscriptions that are not current', function (string $estado) {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create(['slug' => 'cores']);
    $factory = Assinatura::factory()
        ->for($cliente, 'cliente')
        ->for($plano);
    $factory->{$estado}()->create();

    $this->actingAs($cliente)
        ->getJson('/teste/middleware-assinatura')
        ->assertForbidden();
})->with([
    'future' => 'futura',
    'expired' => 'vencida',
    'canceled' => 'cancelada',
]);

it('does not modify a subscription while checking access', function () {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create(['slug' => 'cores']);
    $assinatura = Assinatura::factory()
        ->vigente()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create();
    $valoresOriginais = $assinatura->getRawOriginal();

    $this->actingAs($cliente)
        ->getJson('/teste/middleware-assinatura')
        ->assertSuccessful();

    expect($assinatura->fresh()->getRawOriginal())->toEqual($valoresOriginais);
});

it('applies the wheel subscription middleware only to wheel module routes', function () {
    $rotasDoModulo = [
        'chillbet.wheel',
        'analises.index',
        'api.chillbet.wheel.latest',
        'chillbet.wheel.socket-token',
    ];

    foreach ($rotasDoModulo as $nome) {
        $middlewares = collect(
            Route::getRoutes()
                ->getByName($nome)
                ->gatherMiddleware(),
        );

        expect($middlewares)
            ->toContain('assinatura:wheel')
            ->not->toContain('perfil');
    }

    $rotasSemAssinatura = [
        'dashboard',
        'assinatura.indisponivel',
        'admin.clientes.index',
        'admin.planos.index',
    ];

    foreach ($rotasSemAssinatura as $nome) {
        $middlewares = collect(
            Route::getRoutes()
                ->getByName($nome)
                ->gatherMiddleware(),
        );

        expect($middlewares->contains(
            fn (string $middleware) => Str::startsWith($middleware, 'assinatura:'),
        ))->toBeFalse();
    }
});

it('allows a customer with the wheel plan to access the real module route', function () {
    $cliente = User::factory()->create();
    $plano = Plano::factory()->create(['slug' => 'wheel']);
    Assinatura::factory()
        ->vigente()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create();

    $this->actingAs($cliente)
        ->get(route('chillbet.wheel'))
        ->assertSuccessful();
});

it('allows a customer with the universal trial to access the real module route', function () {
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

    $this->actingAs($cliente)
        ->get(route('chillbet.wheel'))
        ->assertSuccessful();
});

it('allows administrative profiles to access the real module route without a plan', function (string $perfil) {
    $usuario = User::factory()->create(['perfil' => $perfil]);

    $this->actingAs($usuario)
        ->get(route('chillbet.wheel'))
        ->assertSuccessful();
})->with([
    'administrator' => 'administrador',
    'analyst' => 'analista',
]);

it('blocks real wheel routes when the customer has no subscription', function () {
    $cliente = User::factory()->create();
    Plano::factory()->create([
        'nome' => 'Wheel-Chillbet',
        'slug' => 'wheel',
    ]);

    $this->actingAs($cliente)
        ->get(route('chillbet.wheel'))
        ->assertRedirect(route('assinatura.indisponivel'));

    $this->actingAs($cliente)
        ->get(route('assinatura.indisponivel'))
        ->assertSuccessful()
        ->assertSee('Wheel-Chillbet');
});

it('returns forbidden from real wheel json routes when the customer has no subscription', function (string $rota) {
    $cliente = User::factory()->create();

    $this->actingAs($cliente)
        ->getJson(route($rota))
        ->assertForbidden()
        ->assertExactJson([
            'message' => 'Seu acesso a este módulo não está disponível ou o período contratado terminou.',
        ]);
})->with([
    'history API' => 'api.chillbet.wheel.latest',
    'socket token API' => 'chillbet.wheel.socket-token',
]);

it('keeps the unavailable page authenticated and outside subscription middleware', function () {
    $middlewares = collect(
        Route::getRoutes()
            ->getByName('assinatura.indisponivel')
            ->gatherMiddleware(),
    );

    expect($middlewares)
        ->toContain('auth')
        ->not->toContain('assinatura');
});
