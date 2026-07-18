<?php

use App\Models\User;
use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(PlanoSeeder::class);
});

it('creates users with the client profile by default', function () {
    $user = User::factory()->create();

    expect($user->perfil)->toBe('cliente');
});

it('does not accept administrative fields from public registration', function () {
    $this->post(route('register.store'), [
        'name' => 'Cliente Teste',
        'email' => 'cliente@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'cpf_cnpj' => '12345678901',
        'telefone' => '11999999999',
        'perfil' => 'administrador',
    ])->assertRedirect('/dashboard');

    $user = User::query()
        ->where('email', 'cliente@example.com')
        ->firstOrFail();

    expect($user)
        ->perfil->toBe('cliente')
        ->cpf_cnpj->toBeNull()
        ->telefone->toBeNull();
});

it('soft deletes users without removing their records physically', function () {
    $user = User::factory()->create();

    $user->delete();

    $this->assertSoftDeleted($user);
    expect(User::withTrashed()->find($user->id))->not->toBeNull();
});
