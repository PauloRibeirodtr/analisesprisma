<?php

use App\Models\Assinatura;
use App\Models\Pagamento;
use App\Models\Plano;
use App\Models\User;
use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Date::setTestNow('2026-07-17 12:00:00');
    $this->seed(PlanoSeeder::class);
    $this->administrador = User::factory()->create([
        'perfil' => 'administrador',
    ]);
});

afterEach(function () {
    Date::setTestNow();
});

it('protects customer management by authentication and administrative profile', function () {
    $this->get(route('admin.clientes.index'))
        ->assertRedirect(route('login'));

    $cliente = User::factory()->create(['perfil' => 'cliente']);

    $this->actingAs($cliente)
        ->get(route('admin.clientes.index'))
        ->assertForbidden();
});

it('allows both administrative profiles to access customer management', function (string $perfil) {
    $usuario = User::factory()->create(['perfil' => $perfil]);

    $this->actingAs($usuario)
        ->get(route('admin.clientes.index'))
        ->assertSuccessful()
        ->assertSee('Clientes')
        ->assertSee('Análises')
        ->assertSee('PRISMA');
})->with([
    'administrator' => 'administrador',
    'analyst' => 'analista',
]);

it('lists only customers with current subscription count and next expiration', function () {
    $cliente = User::factory()->create([
        'perfil' => 'cliente',
        'name' => 'Cliente Consultado',
        'email' => 'consultado@example.com',
    ]);
    $analista = User::factory()->create([
        'perfil' => 'analista',
        'email' => 'fora-da-lista@example.com',
    ]);

    foreach ([5, 15] as $dias) {
        Assinatura::factory()
            ->vigente()
            ->for($cliente, 'cliente')
            ->create([
                'termino_em' => now()->addDays($dias),
            ]);
    }

    Assinatura::factory()
        ->vencida()
        ->for($cliente, 'cliente')
        ->create();

    $response = $this->actingAs($this->administrador)
        ->get(route('admin.clientes.index'));
    $clienteListado = $response->viewData('clientes')
        ->firstWhere('id', $cliente->id);

    $response
        ->assertSuccessful()
        ->assertSee($cliente->email)
        ->assertDontSee($analista->email);

    expect($clienteListado)
        ->not->toBeNull()
        ->assinaturas_vigentes_count->toBe(2)
        ->proximo_vencimento->toDateTimeString()
        ->toBe('2026-07-22 12:00:00');
});

it('searches customers by their cadastral information', function () {
    User::factory()->create([
        'perfil' => 'cliente',
        'name' => 'Maria Prisma',
        'cpf_cnpj' => '12345678901',
    ]);
    User::factory()->create([
        'perfil' => 'cliente',
        'name' => 'Outro Cliente',
    ]);

    $this->actingAs($this->administrador)
        ->get(route('admin.clientes.index', ['busca' => '12345678901']))
        ->assertSuccessful()
        ->assertSee('Maria Prisma')
        ->assertDontSee('Outro Cliente');
});

it('shows every administrative registration field and profile option', function () {
    $this->actingAs($this->administrador)
        ->get(route('admin.clientes.create'))
        ->assertSuccessful()
        ->assertSee('name="cpf_cnpj"', false)
        ->assertSee('name="telefone"', false)
        ->assertSee('name="perfil"', false)
        ->assertSee('value="cliente"', false)
        ->assertSee('value="analista"', false)
        ->assertSee('value="administrador"', false)
        ->assertSee('name="password"', false)
        ->assertSee('admin-prisma-', false)
        ->assertSee('Dados por todos os ângulos.');
});

it('limits the analyst registration form to the customer profile', function () {
    $analista = User::factory()->create(['perfil' => 'analista']);

    $this->actingAs($analista)
        ->get(route('admin.clientes.create'))
        ->assertSuccessful()
        ->assertSee('value="cliente"', false)
        ->assertDontSee('value="analista"', false)
        ->assertDontSee('value="administrador"', false)
        ->assertSee('Analistas podem cadastrar e editar somente clientes.');
});

it('creates a customer with a universal trial from the administrative form', function () {
    $response = $this->actingAs($this->administrador)
        ->post(route('admin.clientes.store'), [
            'name' => 'Novo Cliente',
            'email' => 'novo-cliente@example.com',
            'cpf_cnpj' => '12345678901',
            'telefone' => '11999999999',
            'perfil' => 'cliente',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

    $cliente = User::query()
        ->where('email', 'novo-cliente@example.com')
        ->firstOrFail();

    $response->assertRedirect(route('admin.clientes.show', $cliente));

    expect($cliente)
        ->cpf_cnpj->toBe('12345678901')
        ->telefone->toBe('11999999999')
        ->perfil->toBe('cliente')
        ->and(Hash::check('Password123!', $cliente->password))->toBeTrue()
        ->and($cliente->assinaturas()->count())->toBe(1)
        ->and($cliente->possuiPlanoVigente('qualquer-modulo'))->toBeTrue();
});

it('allows analysts to register customers with the universal trial', function () {
    $analista = User::factory()->create(['perfil' => 'analista']);

    $this->actingAs($analista)
        ->post(route('admin.clientes.store'), [
            'name' => 'Cliente do Analista',
            'email' => 'cliente-do-analista@example.com',
            'perfil' => 'cliente',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])
        ->assertRedirect();

    $cliente = User::query()
        ->where('email', 'cliente-do-analista@example.com')
        ->firstOrFail();

    expect($cliente->perfil)->toBe('cliente')
        ->and($cliente->assinaturas()->count())->toBe(1);
});

it('prevents analysts from registering administrative profiles', function (string $perfil) {
    $analista = User::factory()->create(['perfil' => 'analista']);

    $this->actingAs($analista)
        ->post(route('admin.clientes.store'), [
            'name' => "Perfil indevido {$perfil}",
            'email' => "perfil-indevido-{$perfil}@example.com",
            'perfil' => $perfil,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('users', [
        'email' => "perfil-indevido-{$perfil}@example.com",
    ]);
})->with([
    'administrator' => 'administrador',
    'analyst' => 'analista',
]);

it('creates administrative users without assigning a subscription', function (string $perfil) {
    $this->actingAs($this->administrador)
        ->post(route('admin.clientes.store'), [
            'name' => "Novo {$perfil}",
            'email' => "{$perfil}@example.com",
            'perfil' => $perfil,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])
        ->assertRedirect();

    $usuario = User::query()
        ->where('email', "{$perfil}@example.com")
        ->firstOrFail();

    expect($usuario->perfil)->toBe($perfil)
        ->and($usuario->assinaturas()->count())->toBe(0);
})->with([
    'administrator' => 'administrador',
    'analyst' => 'analista',
]);

it('validates the administrative registration data', function () {
    $this->actingAs($this->administrador)
        ->post(route('admin.clientes.store'), [
            'name' => '',
            'email' => 'email-invalido',
            'perfil' => 'superusuario',
            'password' => '123',
            'password_confirmation' => 'diferente',
        ])
        ->assertSessionHasErrors([
            'name',
            'email',
            'perfil',
            'password',
        ]);
});

it('updates basic user data without changing the password or history', function () {
    $cliente = User::factory()->create([
        'perfil' => 'cliente',
        'password' => 'Password123!',
    ]);
    $assinatura = Assinatura::factory()
        ->for($cliente, 'cliente')
        ->create();
    $senhaOriginal = $cliente->password;

    $this->actingAs($this->administrador)
        ->put(route('admin.clientes.update', $cliente), [
            'name' => 'Cliente Atualizado',
            'email' => 'atualizado@example.com',
            'cpf_cnpj' => '11222333000144',
            'telefone' => '1133334444',
            'perfil' => 'cliente',
        ])
        ->assertRedirect(route('admin.clientes.show', $cliente));

    $cliente->refresh();

    expect($cliente)
        ->name->toBe('Cliente Atualizado')
        ->email->toBe('atualizado@example.com')
        ->cpf_cnpj->toBe('11222333000144')
        ->telefone->toBe('1133334444')
        ->password->toBe($senhaOriginal)
        ->and($cliente->assinaturas()->sole()->is($assinatura))->toBeTrue();
});

it('prevents analysts from viewing or editing administrative accounts', function (string $perfil) {
    $analista = User::factory()->create(['perfil' => 'analista']);
    $alvo = User::factory()->create(['perfil' => $perfil]);

    $this->actingAs($analista)
        ->get(route('admin.clientes.show', $alvo))
        ->assertForbidden();

    $this->actingAs($analista)
        ->get(route('admin.clientes.edit', $alvo))
        ->assertForbidden();

    $this->actingAs($analista)
        ->put(route('admin.clientes.update', $alvo), [
            'name' => 'Alteração indevida',
            'email' => $alvo->email,
            'perfil' => 'cliente',
        ])
        ->assertForbidden();

    expect($alvo->refresh())
        ->name->not->toBe('Alteração indevida')
        ->perfil->toBe($perfil);
})->with([
    'administrator target' => 'administrador',
    'analyst target' => 'analista',
]);

it('prevents analysts from promoting customers to administrative profiles', function (string $perfil) {
    $analista = User::factory()->create(['perfil' => 'analista']);
    $cliente = User::factory()->create(['perfil' => 'cliente']);

    $this->actingAs($analista)
        ->put(route('admin.clientes.update', $cliente), [
            'name' => $cliente->name,
            'email' => $cliente->email,
            'perfil' => $perfil,
        ])
        ->assertForbidden();

    expect($cliente->refresh()->perfil)->toBe('cliente');
})->with([
    'administrator' => 'administrador',
    'analyst' => 'analista',
]);

it('allows administrators to manage administrative accounts', function () {
    $analista = User::factory()->create(['perfil' => 'analista']);

    $this->actingAs($this->administrador)
        ->get(route('admin.clientes.edit', $analista))
        ->assertSuccessful()
        ->assertSee('value="administrador"', false);

    $this->actingAs($this->administrador)
        ->put(route('admin.clientes.update', $analista), [
            'name' => $analista->name,
            'email' => $analista->email,
            'perfil' => 'administrador',
        ])
        ->assertRedirect(route('admin.clientes.show', $analista));

    expect($analista->refresh()->perfil)->toBe('administrador');
});

it('renders the edit screen with all basic fields', function () {
    $cliente = User::factory()->create([
        'perfil' => 'cliente',
        'cpf_cnpj' => '12345678901',
        'telefone' => '11999999999',
    ]);

    $this->actingAs($this->administrador)
        ->get(route('admin.clientes.edit', $cliente))
        ->assertSuccessful()
        ->assertSee($cliente->name)
        ->assertSee($cliente->email)
        ->assertSee('12345678901')
        ->assertSee('11999999999')
        ->assertSee('name="perfil"', false)
        ->assertDontSee('name="password"', false);
});

it('shows calculated subscription situations and preserved payment history', function () {
    $cliente = User::factory()->create([
        'perfil' => 'cliente',
        'name' => 'Cliente Detalhado',
    ]);
    $plano = Plano::factory()->create(['nome' => 'Plano Atual']);
    $assinaturaVigente = Assinatura::factory()
        ->vigente()
        ->for($cliente, 'cliente')
        ->for($plano)
        ->create(['nome_plano' => 'Nome histórico do plano']);
    Assinatura::factory()
        ->futura()
        ->for($cliente, 'cliente')
        ->create();
    Assinatura::factory()
        ->vencida()
        ->for($cliente, 'cliente')
        ->create();
    Assinatura::factory()
        ->cancelada()
        ->for($cliente, 'cliente')
        ->create();
    Pagamento::factory()
        ->for($cliente, 'cliente')
        ->for($assinaturaVigente)
        ->for($this->administrador, 'registradoPor')
        ->create([
            'valor' => '149.90',
            'observacao' => 'Pagamento preservado.',
        ]);

    $this->actingAs($this->administrador)
        ->get(route('admin.clientes.show', $cliente))
        ->assertSuccessful()
        ->assertSee('Vigente')
        ->assertSee('Futura')
        ->assertSee('Vencida')
        ->assertSee('Cancelada')
        ->assertSee('Nome histórico do plano')
        ->assertSee('R$ 149,90')
        ->assertSee($this->administrador->name)
        ->assertSee('Pagamento preservado.');
});

it('hides the customer menu from customer profiles', function () {
    $cliente = User::factory()->create(['perfil' => 'cliente']);

    $this->actingAs($cliente)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertDontSee(route('admin.clientes.index'), false);
});

it('shows the customer menu to administrative profiles', function () {
    $this->actingAs($this->administrador)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee(route('admin.clientes.index'), false);
});
