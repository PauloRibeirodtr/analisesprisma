<?php

use App\Models\Assinatura;
use App\Models\Plano;
use App\Models\User;
use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(PlanoSeeder::class);
    $this->administrador = User::factory()->create([
        'perfil' => 'administrador',
    ]);
});

it('protects plan management by authentication and administrator profile', function () {
    $this->get(route('admin.planos.index'))
        ->assertRedirect(route('login'));

    $analista = User::factory()->create(['perfil' => 'analista']);

    $this->actingAs($analista)
        ->get(route('admin.planos.index'))
        ->assertForbidden();

    $this->actingAs($analista)
        ->post(route('admin.planos.store'), [
            'nome' => 'Plano não autorizado',
            'slug' => 'nao-autorizado',
            'valor_padrao' => '10.00',
            'duracao_dias' => 30,
            'disponivel_para_venda' => '1',
        ])
        ->assertForbidden();
});

it('shows the plan menu to administrators', function () {
    $this->actingAs($this->administrador)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee(route('admin.planos.index'), false);
});

it('hides the plan menu from analysts', function () {
    $analista = User::factory()->create(['perfil' => 'analista']);

    $this->actingAs($analista)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertDontSee(route('admin.planos.index'), false);
});

it('lists searchable plans with commercial and historical situations', function () {
    $planoVenda = Plano::factory()->create([
        'nome' => 'Plano Prisma Comercial',
        'slug' => 'prisma-comercial',
        'disponivel_para_venda' => true,
    ]);
    $planoIndisponivel = Plano::factory()->create([
        'nome' => 'Plano Pausado',
        'slug' => 'plano-pausado',
        'disponivel_para_venda' => false,
    ]);
    $planoArquivado = Plano::factory()->create([
        'nome' => 'Plano Histórico',
        'slug' => 'plano-historico',
    ]);
    Assinatura::factory()
        ->for($planoVenda)
        ->create();
    $planoArquivado->delete();

    $this->actingAs($this->administrador)
        ->get(route('admin.planos.index'))
        ->assertSuccessful()
        ->assertSee('Plano Prisma Comercial')
        ->assertSee('Plano Pausado')
        ->assertSee('Plano Histórico')
        ->assertSee('Teste gratuito')
        ->assertSee('À venda')
        ->assertSee('Indisponível')
        ->assertSee('Arquivado')
        ->assertSee('Sistema')
        ->assertSee('prisma-comercial')
        ->assertSee('>1</span>', false);

    $this->actingAs($this->administrador)
        ->get(route('admin.planos.index', [
            'busca' => 'Prisma Comercial',
            'situacao' => 'venda',
        ]))
        ->assertSuccessful()
        ->assertSee('Plano Prisma Comercial')
        ->assertDontSee('Plano Pausado')
        ->assertDontSee('Plano Histórico');

    $this->actingAs($this->administrador)
        ->get(route('admin.planos.index', ['situacao' => 'arquivados']))
        ->assertSuccessful()
        ->assertSee('Plano Histórico')
        ->assertDontSee('Plano Prisma Comercial')
        ->assertDontSee('Plano Pausado');

    expect($planoIndisponivel->disponivel_para_venda)->toBeFalse();
});

it('renders every field required to create a commercial plan', function () {
    $this->actingAs($this->administrador)
        ->get(route('admin.planos.create'))
        ->assertSuccessful()
        ->assertSee('name="nome"', false)
        ->assertSee('name="slug"', false)
        ->assertSee('name="descricao"', false)
        ->assertSee('name="valor_padrao"', false)
        ->assertSee('name="duracao_dias"', false)
        ->assertSee('name="disponivel_para_venda"', false)
        ->assertSee('Cadastrar plano')
        ->assertSee('Análises')
        ->assertSee('PRISMA');
});

it('creates a plan with decimal value duration and sale availability', function () {
    $response = $this->actingAs($this->administrador)
        ->post(route('admin.planos.store'), [
            'nome' => 'Wheel Profissional',
            'slug' => 'wheel-profissional',
            'descricao' => 'Acesso completo às análises do módulo Wheel.',
            'valor_padrao' => '149.90',
            'duracao_dias' => 45,
            'disponivel_para_venda' => '1',
        ]);

    $plano = Plano::query()
        ->where('slug', 'wheel-profissional')
        ->firstOrFail();

    $response
        ->assertRedirect(route('admin.planos.edit', $plano))
        ->assertSessionHas('sucesso', 'Plano cadastrado com sucesso.');

    expect($plano)
        ->nome->toBe('Wheel Profissional')
        ->descricao->toBe('Acesso completo às análises do módulo Wheel.')
        ->valor_padrao->toBe('149.90')
        ->duracao_dias->toBe(45)
        ->disponivel_para_venda->toBeTrue();
});

it('validates plan data reserved slug and uniqueness including archives', function () {
    $planoArquivado = Plano::factory()->create([
        'slug' => 'identificador-existente',
    ]);
    $planoArquivado->delete();

    $this->actingAs($this->administrador)
        ->post(route('admin.planos.store'), [
            'nome' => '',
            'slug' => 'Identificador Inválido',
            'descricao' => str_repeat('a', 2001),
            'valor_padrao' => '-1',
            'duracao_dias' => 0,
            'disponivel_para_venda' => 'valor-invalido',
        ])
        ->assertSessionHasErrors([
            'nome',
            'slug',
            'descricao',
            'valor_padrao',
            'duracao_dias',
            'disponivel_para_venda',
        ]);

    $this->actingAs($this->administrador)
        ->post(route('admin.planos.store'), [
            'nome' => 'Duplicado',
            'slug' => 'identificador-existente',
            'valor_padrao' => '10.00',
            'duracao_dias' => 30,
            'disponivel_para_venda' => '1',
        ])
        ->assertSessionHasErrors('slug');

    $this->actingAs($this->administrador)
        ->post(route('admin.planos.store'), [
            'nome' => 'Teste indevido',
            'slug' => Plano::SLUG_TESTE,
            'valor_padrao' => '0.00',
            'duracao_dias' => 1,
            'disponivel_para_venda' => '0',
        ])
        ->assertSessionHasErrors('slug');
});

it('updates commercial data while preserving slug and subscription snapshots', function () {
    $plano = Plano::factory()->create([
        'nome' => 'Nome original',
        'slug' => 'slug-permanente',
        'valor_padrao' => '80.00',
    ]);
    $assinatura = Assinatura::factory()
        ->for($plano)
        ->create([
            'nome_plano' => 'Nome original',
            'valor_referencia' => '80.00',
        ]);

    $this->actingAs($this->administrador)
        ->get(route('admin.planos.edit', $plano))
        ->assertSuccessful()
        ->assertSee('slug-permanente')
        ->assertSee('O identificador é permanente')
        ->assertDontSee('name="slug"', false);

    $this->actingAs($this->administrador)
        ->put(route('admin.planos.update', $plano), [
            'nome' => 'Nome atualizado',
            'slug' => 'tentativa-de-alteracao',
            'descricao' => 'Nova apresentação comercial.',
            'valor_padrao' => '120.50',
            'duracao_dias' => 60,
            'disponivel_para_venda' => '0',
        ])
        ->assertRedirect(route('admin.planos.edit', $plano))
        ->assertSessionHas('sucesso', 'Plano atualizado com sucesso.');

    $plano->refresh();
    $assinatura->refresh();

    expect($plano)
        ->nome->toBe('Nome atualizado')
        ->slug->toBe('slug-permanente')
        ->descricao->toBe('Nova apresentação comercial.')
        ->valor_padrao->toBe('120.50')
        ->duracao_dias->toBe(60)
        ->disponivel_para_venda->toBeFalse()
        ->and($assinatura)
        ->nome_plano->toBe('Nome original')
        ->valor_referencia->toBe('80.00');
});

it('archives and restores a plan without deleting related subscriptions', function () {
    $plano = Plano::factory()->create();
    $assinatura = Assinatura::factory()
        ->for($plano)
        ->create();

    $this->actingAs($this->administrador)
        ->delete(route('admin.planos.destroy', $plano))
        ->assertRedirect(route('admin.planos.index'))
        ->assertSessionHas(
            'sucesso',
            'Plano arquivado sem apagar seu histórico.',
        );

    $this->assertSoftDeleted($plano);
    $this->assertModelExists($assinatura);
    expect($assinatura->fresh()->plano)
        ->not->toBeNull()
        ->id->toBe($plano->id);

    $planoArquivado = $plano->fresh();

    $this->actingAs($this->administrador)
        ->patch(route('admin.planos.restore', $planoArquivado))
        ->assertRedirect(route('admin.planos.index'))
        ->assertSessionHas('sucesso', 'Plano restaurado com sucesso.');

    expect($planoArquivado->fresh()->trashed())->toBeFalse();
});

it('protects the internal trial plan against editing and archiving', function () {
    $planoTeste = Plano::query()
        ->where('slug', Plano::SLUG_TESTE)
        ->firstOrFail();

    $this->actingAs($this->administrador)
        ->get(route('admin.planos.edit', $planoTeste))
        ->assertForbidden();

    $this->actingAs($this->administrador)
        ->put(route('admin.planos.update', $planoTeste), [
            'nome' => 'Teste alterado',
            'valor_padrao' => '10.00',
            'duracao_dias' => 10,
            'disponivel_para_venda' => '1',
        ])
        ->assertForbidden();

    $this->actingAs($this->administrador)
        ->delete(route('admin.planos.destroy', $planoTeste))
        ->assertForbidden();

    expect($planoTeste->fresh())
        ->nome->toBe('Teste gratuito')
        ->trashed()->toBeFalse();
});
