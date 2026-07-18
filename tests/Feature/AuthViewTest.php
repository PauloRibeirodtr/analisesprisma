<?php

use App\Models\User;

it('renders the guest authentication views', function (string $route, array $parameters = []) {
    $this->get(route($route, $parameters))
        ->assertSuccessful()
        ->assertSee('Análises')
        ->assertSee('PRISMA')
        ->assertSee('Dados por todos os ângulos.')
        ->assertDontSee('PrismaBet');
})->with([
    'login' => ['login'],
    'registration' => ['register'],
    'forgot password' => ['password.request'],
    'reset password' => ['password.reset', ['token' => 'test-token']],
]);

it('renders the authenticated authentication views', function (string $route) {
    $user = new User([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->actingAs($user)
        ->get(route($route))
        ->assertSuccessful()
        ->assertSee('Análises')
        ->assertSee('PRISMA')
        ->assertSee('Dados por todos os ângulos.')
        ->assertDontSee('PrismaBet');
})->with([
    'email verification' => ['verification.notice'],
    'password confirmation' => ['password.confirm'],
]);
