@extends('adminlte::page')

@section('title', 'Cadastrar usuário | Análises Prisma')

@section('content')
    <main class="prisma-admin">
        <x-admin.page-header
            titulo="Cadastrar usuário"
            subtitulo="Preencha os dados cadastrais e escolha o perfil de acesso."
            icone="fas fa-user-plus"
        />

        <section class="prisma-panel prisma-panel--form">
            <div class="prisma-panel__heading">
                <span class="prisma-panel__icon">
                    <i class="fas fa-id-card" aria-hidden="true"></i>
                </span>
                <div>
                    <span class="prisma-panel__kicker">Novo cadastro</span>
                    <h2>Dados do usuário</h2>
                    <p>Clientes recebem automaticamente o período de teste gratuito configurado.</p>
                </div>
            </div>

            <x-admin.usuario-form
                :action="route('admin.clientes.store')"
                :pode-administrar-perfis="$podeAdministrarPerfis"
                texto-botao="Cadastrar usuário"
            />
        </section>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop
