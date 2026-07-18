<?php

use App\Models\Assinatura;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Date::setTestNow('2026-07-17 12:00:00');
});

afterEach(function () {
    Date::setTestNow();
});

it('requires authentication and verified email', function () {
    $this->get(route('minha-assinatura.index'))
        ->assertRedirect(route('login'));

    $usuario = User::factory()->unverified()->create();

    $this->actingAs($usuario)
        ->get(route('minha-assinatura.index'))
        ->assertRedirect(route('verification.notice'));
});

it('shows only the authenticated user data current access and subscription history', function () {
    $usuario = User::factory()->create([
        'name' => 'Cliente da Assinatura',
        'email' => 'minha-assinatura@example.com',
        'perfil' => 'cliente',
    ]);
    $outroUsuario = User::factory()->create(['perfil' => 'cliente']);
    $planoWheel = Plano::factory()->create(['nome' => 'Wheel Estatístico']);
    $planoHistorico = Plano::factory()->create(['nome' => 'Plano Histórico']);

    Assinatura::factory()
        ->vigente()
        ->for($usuario, 'cliente')
        ->for($planoWheel)
        ->create([
            'nome_plano' => 'Wheel Estatístico',
            'inicio_em' => now()->subDay(),
            'termino_em' => now()->addDays(5),
        ]);
    Assinatura::factory()
        ->vencida()
        ->for($usuario, 'cliente')
        ->for($planoHistorico)
        ->create([
            'nome_plano' => 'Plano Histórico',
            'termino_em' => now()->subDays(3),
        ]);
    Assinatura::factory()
        ->vigente()
        ->for($outroUsuario, 'cliente')
        ->create(['nome_plano' => 'Plano de outro cliente']);

    $this->actingAs($usuario)
        ->get(route('minha-assinatura.index'))
        ->assertSuccessful()
        ->assertSee('Cliente da Assinatura')
        ->assertSee('minha-assinatura@example.com')
        ->assertSee('22/07/2026')
        ->assertSee('Wheel Estatístico')
        ->assertSee('Plano Histórico')
        ->assertSee('Vigente')
        ->assertSee('Vencida')
        ->assertDontSee('Plano de outro cliente')
        ->assertSee('name="current_password"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="password_confirmation"', false);
});

it('shows every current subscription and highlights the nearest expiration', function () {
    $usuario = User::factory()->create(['perfil' => 'cliente']);

    foreach ([
        ['nome' => 'Plano mais longo', 'dias' => 12],
        ['nome' => 'Plano mais próximo', 'dias' => 2],
    ] as $dados) {
        Assinatura::factory()
            ->vigente()
            ->for($usuario, 'cliente')
            ->create([
                'nome_plano' => $dados['nome'],
                'termino_em' => now()->addDays($dados['dias']),
            ]);
    }

    $this->actingAs($usuario)
        ->get(route('minha-assinatura.index'))
        ->assertSuccessful()
        ->assertSee('acessos vigentes')
        ->assertSee('19/07/2026')
        ->assertSee('Plano mais longo')
        ->assertSee('Plano mais próximo');
});

it('shows unrestricted access for administrative profiles without requiring a subscription', function (string $perfil) {
    $usuario = User::factory()->create(['perfil' => $perfil]);

    $this->actingAs($usuario)
        ->get(route('minha-assinatura.index'))
        ->assertSuccessful()
        ->assertSee('Acesso administrativo')
        ->assertSee('Este perfil não depende de uma assinatura.')
        ->assertDontSee('Sem assinatura vigente');
})->with([
    'administrator' => 'administrador',
    'analyst' => 'analista',
]);

it('updates the authenticated user password after confirming the current password', function () {
    $usuario = User::factory()->create([
        'password' => 'SenhaAtual123!',
    ]);

    $this->actingAs($usuario)
        ->patch(route('minha-assinatura.senha.update'), [
            'current_password' => 'SenhaAtual123!',
            'password' => 'NovaSenha123!',
            'password_confirmation' => 'NovaSenha123!',
        ])
        ->assertRedirect()
        ->assertSessionHas(
            'sucesso_senha',
            'Sua senha foi atualizada com segurança.',
        );

    expect(Hash::check('NovaSenha123!', $usuario->refresh()->password))
        ->toBeTrue();
});

it('rejects an incorrect current password and keeps the existing password', function () {
    $usuario = User::factory()->create([
        'password' => 'SenhaAtual123!',
    ]);
    $senhaOriginal = $usuario->password;

    $this->actingAs($usuario)
        ->from(route('minha-assinatura.index'))
        ->patch(route('minha-assinatura.senha.update'), [
            'current_password' => 'SenhaIncorreta123!',
            'password' => 'NovaSenha123!',
            'password_confirmation' => 'NovaSenha123!',
        ])
        ->assertRedirect(route('minha-assinatura.index'))
        ->assertSessionHasErrors('current_password');

    expect($usuario->refresh()->password)->toBe($senhaOriginal);
});

it('validates confirmation and strength of the new password', function () {
    $usuario = User::factory()->create([
        'password' => 'SenhaAtual123!',
    ]);

    $this->actingAs($usuario)
        ->patch(route('minha-assinatura.senha.update'), [
            'current_password' => 'SenhaAtual123!',
            'password' => '123',
            'password_confirmation' => 'diferente',
        ])
        ->assertSessionHasErrors('password');
});
