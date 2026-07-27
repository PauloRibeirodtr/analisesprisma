@extends('adminlte::page')

@section('title', 'Blaze Double - Branco | Análises Prisma')

@section('content')
    <main class="prisma-admin prisma-wheel-page">
        <x-admin.page-header
            titulo="Blaze Double - Branco"
            subtitulo="Acompanhe o último giro do Double e mantenha o foco operacional no branco de 14x."
            icone="fas fa-bullseye"
            contexto="Análises em tempo real"
        />

        <section class="prisma-wheel-intro" aria-labelledby="double-intro-title">
            <span class="prisma-wheel-intro__icon">
                <i class="fas fa-crosshairs" aria-hidden="true"></i>
            </span>

            <div class="prisma-wheel-intro__content">
                <span class="prisma-panel__kicker">Operação Double Branco</span>
                <h2 id="double-intro-title">O último resultado como ponto de partida</h2>
                <p>
                    Acompanhe cada giro em tempo real e use esta área como base para as próximas
                    análises voltadas ao branco. Indicadores históricos apoiam a leitura, mas
                    não garantem resultados futuros.
                </p>
            </div>

            <div class="prisma-wheel-intro__signals" aria-label="Características do painel">
                <span>
                    <i class="fas fa-bolt" aria-hidden="true"></i>
                    Atualização ao vivo
                </span>
                <span>
                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                    Conexão autenticada
                </span>
            </div>
        </section>

        <section class="prisma-wheel-workspace" aria-label="Painel de operações do Double Branco">
            <div id="blaze-double-branco-app"></div>
        </section>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop

@push('js')
    @vite('resources/js/app.js')
@endpush
