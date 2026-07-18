@extends('adminlte::page')

@section('title', 'Editar '.$plano->nome.' | Análises Prisma')

@section('content')
    <main class="prisma-admin">
        <x-admin.page-header
            titulo="Editar plano"
            :subtitulo="'Atualize as condições comerciais de '.$plano->nome.'.'"
            icone="fas fa-edit"
        >
            <x-slot:acoes>
                <a href="{{ route('admin.planos.index') }}" class="btn prisma-btn prisma-btn--ghost">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Planos
                </a>
            </x-slot:acoes>
        </x-admin.page-header>

        @if (session('sucesso'))
            <div class="prisma-alert" role="alert">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                {{ session('sucesso') }}
            </div>
        @endif

        <section class="prisma-panel prisma-panel--form">
            <div class="prisma-panel__heading">
                <span class="prisma-panel__icon">
                    <i class="fas fa-cube" aria-hidden="true"></i>
                </span>
                <div>
                    <span class="prisma-panel__kicker">Produto comercial</span>
                    <h2>Dados do plano</h2>
                    <p>Alterações não sobrescrevem os dados históricos das assinaturas existentes.</p>
                </div>
            </div>

            <x-admin.plano-form
                :action="route('admin.planos.update', $plano)"
                method="PUT"
                :plano="$plano"
                texto-botao="Salvar alterações"
            />
        </section>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop
