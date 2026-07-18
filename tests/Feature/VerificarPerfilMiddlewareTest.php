<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    User::query()->count();

    Route::middleware([
        'web',
        'perfil:administrador,analista',
    ])->get('/teste/middleware-perfil', fn () => response()->json([
        'permitido' => true,
    ]));
});

it('redirects an unauthenticated web request to login', function () {
    $this->get('/teste/middleware-perfil')
        ->assertRedirect(route('login'));
});

it('returns unauthorized for an unauthenticated json request', function () {
    $this->getJson('/teste/middleware-perfil')
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
});

it('allows administrative profiles without a subscription', function (string $perfil) {
    $usuario = User::factory()->create(['perfil' => $perfil]);

    $this->actingAs($usuario)
        ->getJson('/teste/middleware-perfil')
        ->assertSuccessful()
        ->assertExactJson(['permitido' => true]);
})->with([
    'administrator' => 'administrador',
    'analyst' => 'analista',
]);

it('blocks a customer from the administrative area', function () {
    $cliente = User::factory()->create(['perfil' => 'cliente']);

    $this->actingAs($cliente)
        ->get('/teste/middleware-perfil')
        ->assertForbidden()
        ->assertSee('Você não possui permissão para acessar esta área.');
});

it('returns forbidden without exposing allowed profiles to json clients', function () {
    $cliente = User::factory()->create(['perfil' => 'cliente']);

    $this->actingAs($cliente)
        ->getJson('/teste/middleware-perfil')
        ->assertForbidden()
        ->assertExactJson([
            'message' => 'Você não possui permissão para acessar esta área.',
        ]);
});
