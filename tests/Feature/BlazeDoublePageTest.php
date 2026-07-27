<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

function blazeDoubleTestUser(bool $verified = true): User
{
    $user = new User([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'perfil' => 'cliente',
    ]);
    $user->forceFill([
        'id' => 42,
        'email_verified_at' => $verified ? now() : null,
    ]);

    return $user;
}

it('requires authentication to access the Blaze Double page', function () {
    $this->get(route('blaze.double-branco'))
        ->assertRedirect(route('login'));
});

it('requires a verified email to access the Blaze Double page', function () {
    $this->actingAs(blazeDoubleTestUser(verified: false))
        ->get(route('blaze.double-branco'))
        ->assertRedirect(route('verification.notice'));
});

it('renders the Blaze Double page for a verified user without a subscription', function () {
    $this->actingAs(blazeDoubleTestUser())
        ->get(route('blaze.double-branco'))
        ->assertSuccessful()
        ->assertSee('Blaze Double - Branco')
        ->assertSee('Double - Branco')
        ->assertSee('O último resultado como ponto de partida')
        ->assertSee('id="blaze-double-branco-app"', false)
        ->assertSee('type="module"', false);
});

it('keeps Blaze Double routes outside subscription middleware', function () {
    foreach (['blaze.double-branco', 'blaze.double.socket-token'] as $routeName) {
        $middlewares = collect(
            Route::getRoutes()
                ->getByName($routeName)
                ->gatherMiddleware(),
        );

        expect($middlewares)
            ->toContain('auth')
            ->toContain('verified');

        expect($middlewares->contains(
            fn (string $middleware) => Str::startsWith($middleware, 'assinatura:'),
        ))->toBeFalse();
    }
});
