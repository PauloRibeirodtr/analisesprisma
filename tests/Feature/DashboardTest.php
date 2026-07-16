<?php

use App\Models\User;

it('renders the dashboard for an authenticated user', function () {
    $user = new User([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    $user->email_verified_at = now();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('test@example.com')
        ->assertSee('Wheel')
        ->assertSee(route('chillbet.wheel'), false)
        ->assertSee('Planos')
        ->assertSee('Vídeos')
        ->assertSee('Outros jogos')
        ->assertDontSee('id="chillbet-wheel-app"', false);
});

it('renders the Chillbet Wheel history for an authenticated user', function () {
    $user = new User([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    $user->email_verified_at = now();

    $this->actingAs($user)
        ->get(route('chillbet.wheel'))
        ->assertSuccessful()
        ->assertSee('Chillbet Wheel')
        ->assertSee('id="chillbet-wheel-app"', false)
        ->assertSee('data-history-limit="50"', false)
        ->assertSee('data-analysis-limit="250"', false)
        ->assertSee('type="module"', false);
});

it('redirects the legacy analyses page to Chillbet Wheel', function () {
    $user = new User([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    $user->email_verified_at = now();

    $this->actingAs($user)
        ->get(route('analises.index'))
        ->assertRedirect('/chillbet/wheel');
});
