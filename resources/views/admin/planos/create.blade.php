@extends('adminlte::page')

@section('title', 'Cadastrar plano | Análises Prisma')

@section('content')
    <main class="prisma-admin">
        <x-admin.page-header
            titulo="Cadastrar plano"
            subtitulo="Crie um produto que poderá liberar um módulo aos clientes."
            icone="fas fa-plus-circle"
        />

        <section class="prisma-panel prisma-panel--form">
            <div class="prisma-panel__heading">
                <span class="prisma-panel__icon">
                    <i class="fas fa-cube" aria-hidden="true"></i>
                </span>
                <div>
                    <span class="prisma-panel__kicker">Novo produto</span>
                    <h2>Dados do plano</h2>
                    <p>Valor e duração funcionarão como sugestões nas contratações.</p>
                </div>
            </div>

            <x-admin.plano-form
                :action="route('admin.planos.store')"
                texto-botao="Cadastrar plano"
            />
        </section>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop
