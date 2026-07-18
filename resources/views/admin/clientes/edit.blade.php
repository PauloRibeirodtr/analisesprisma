@extends('adminlte::page')

@section('title', 'Editar usuário | Análises Prisma')

@section('content')
    <main class="prisma-admin">
        <x-admin.page-header
            titulo="Editar usuário"
            subtitulo="Atualize os dados básicos e o perfil de acesso."
            icone="fas fa-user-edit"
        >
            <x-slot:acoes>
                <a href="{{ route('admin.clientes.show', $cliente) }}" class="btn prisma-btn prisma-btn--ghost">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                    Ver detalhes
                </a>
            </x-slot:acoes>
        </x-admin.page-header>

        <section class="prisma-panel prisma-panel--form">
            <div class="prisma-panel__heading">
                <span class="prisma-panel__icon">
                    <i class="fas fa-address-card" aria-hidden="true"></i>
                </span>
                <div>
                    <span class="prisma-panel__kicker">Cadastro #{{ $cliente->id }}</span>
                    <h2>{{ $cliente->name }}</h2>
                    <p>Alterações de perfil modificam as permissões, mas preservam todo o histórico.</p>
                </div>
            </div>

            <x-admin.usuario-form
                :action="route('admin.clientes.update', $cliente)"
                method="PUT"
                :usuario="$cliente"
                :pode-administrar-perfis="$podeAdministrarPerfis"
                texto-botao="Salvar alterações"
            />
        </section>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop
