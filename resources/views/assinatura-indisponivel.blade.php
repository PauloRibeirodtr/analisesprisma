@extends('adminlte::page')

@section('title', 'Acesso indisponível | Análises Prisma')

@section('content')
    <main class="prisma-admin prisma-access-page">
        <x-admin.page-header
            titulo="Acesso indisponível"
            subtitulo="Este módulo precisa de um período de acesso válido."
            icone="fas fa-lock"
        />

        <section class="prisma-access-card">
            <div class="prisma-access-card__visual" aria-hidden="true">
                <span>
                    <i class="fas fa-lock"></i>
                </span>
                <i class="fas fa-cube prisma-access-card__module"></i>
            </div>

            <div class="prisma-access-card__content">
                <span class="prisma-panel__kicker">Controle de acesso</span>
                <h2>Seu acesso precisa ser renovado</h2>
                <p class="prisma-access-card__message">
                    Seu acesso a este módulo não está disponível ou o período contratado terminou.
                </p>

                @if ($nomeModulo || $ultimoVencimento)
                    <dl class="prisma-access-details">
                        @if ($nomeModulo)
                            <div>
                                <dt>Módulo solicitado</dt>
                                <dd>{{ $nomeModulo }}</dd>
                            </div>
                        @endif

                        @if ($ultimoVencimento)
                            <div>
                                <dt>Último período de acesso</dt>
                                <dd>Encerrado em {{ $ultimoVencimento->format('d/m/Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>
                @endif

                <div class="prisma-access-guidance">
                    <i class="fas fa-headset" aria-hidden="true"></i>
                    <div>
                        <strong>Precisa continuar utilizando este módulo?</strong>
                        <span>Entre em contato com o suporte ou com seu analista responsável para renovar o acesso.</span>
                    </div>
                </div>

                <a href="{{ route('dashboard') }}" class="btn prisma-btn prisma-btn--primary">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Voltar ao painel
                </a>
            </div>
        </section>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop
