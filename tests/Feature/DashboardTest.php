<?php

use App\Models\Assinatura;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

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
        ->assertSee('Análises Prisma')
        ->assertSee('sem achismos')
        ->assertSee('Dados reais')
        ->assertSee('Wheel')
        ->assertSee(route('chillbet.wheel'), false)
        ->assertSee('Vídeos')
        ->assertSee('Minha Assinatura')
        ->assertSee(route('minha-assinatura.index'), false)
        ->assertDontSee('PrismaBet')
        ->assertDontSee('Outros jogos')
        ->assertDontSee('data-dashboard-card="planos"', false)
        ->assertDontSee('id="chillbet-wheel-app"', false);
});

it('renders the Chillbet Wheel history for an authenticated user', function () {
    $user = User::factory()->create();
    $plano = Plano::factory()->create(['slug' => 'wheel']);
    Assinatura::factory()
        ->vigente()
        ->for($user, 'cliente')
        ->for($plano)
        ->create();

    $this->actingAs($user)
        ->get(route('chillbet.wheel'))
        ->assertSuccessful()
        ->assertSee('Chillbet Wheel')
        ->assertSee('Análises Prisma')
        ->assertSee('Análises estatísticas')
        ->assertSee('Leitura baseada no histórico real')
        ->assertSee('prisma-wheel-page', false)
        ->assertSee('prisma-wheel-workspace', false)
        ->assertSee('id="chillbet-wheel-app"', false)
        ->assertSee('data-history-limit="50"', false)
        ->assertSee('data-analysis-limit="250"', false)
        ->assertSee('type="module"', false);
});

it('redirects the legacy analyses page to Chillbet Wheel', function () {
    $user = User::factory()->create();
    $plano = Plano::factory()->create(['slug' => 'wheel']);
    Assinatura::factory()
        ->vigente()
        ->for($user, 'cliente')
        ->for($plano)
        ->create();

    $this->actingAs($user)
        ->get(route('analises.index'))
        ->assertRedirect('/chillbet/wheel');
});
