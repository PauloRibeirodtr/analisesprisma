@extends('adminlte::page')

@section('title', 'Chillbet Wheel | Análises Prisma')

@section('content')
    <main class="prisma-admin prisma-wheel-page">
        <x-admin.page-header
            titulo="Chillbet Wheel"
            subtitulo="Histórico visual e análises estatísticas dos resultados da Wheel."
            icone="fas fa-circle-notch"
            contexto="Análises estatísticas"
        />

        <section class="prisma-wheel-intro" aria-labelledby="wheel-intro-title">
            <span class="prisma-wheel-intro__icon">
                <i class="fas fa-wave-square" aria-hidden="true"></i>
            </span>

            <div class="prisma-wheel-intro__content">
                <span class="prisma-panel__kicker">Informação em contexto</span>
                <h2 id="wheel-intro-title">Leitura baseada no histórico real</h2>
                <p>
                    Selecione a base analisada e acompanhe padrões, intervalos e distribuições
                    calculados a partir dos resultados registrados. Os indicadores representam
                    comportamento histórico e não garantem resultados futuros.
                </p>
            </div>

            <div class="prisma-wheel-intro__signals" aria-label="Características da análise">
                <span>
                    <i class="fas fa-database" aria-hidden="true"></i>
                    Base histórica
                </span>
                <span>
                    <i class="fas fa-bolt" aria-hidden="true"></i>
                    Atualização em tempo real
                </span>
            </div>
        </section>

        <section class="prisma-wheel-workspace" aria-label="Painel de análises da Wheel">
            <div
                id="chillbet-wheel-app"
                data-history-limit="50"
                data-analysis-limit="2000"
            ></div>
        </section>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop

@push('js')
    @vite('resources/js/app.js')
@endpush
