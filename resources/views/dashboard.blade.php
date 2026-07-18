@extends('adminlte::page')

@section('title', 'Dashboard | Análises Prisma')

@section('content')
    <main class="prisma-admin prisma-dashboard">
        <section class="prisma-dashboard-hero">
            <div class="prisma-dashboard-hero__content">
                <span class="prisma-eyebrow">
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    Inteligência estatística
                </span>

                <h1>Olá, {{ auth()->user()->name }}.</h1>

                <p>
                    O Análises Prisma transforma históricos e ocorrências reais em uma leitura
                    estatística clara. Cada análise parte de dados registrados, padrões mensuráveis
                    e fatos — sem achismos, promessas ou decisões baseadas apenas em intuição.
                </p>

                <div class="prisma-dashboard-hero__principles" aria-label="Princípios do Análises Prisma">
                    <span>
                        <i class="fas fa-database" aria-hidden="true"></i>
                        Dados reais
                    </span>
                    <span>
                        <i class="fas fa-wave-square" aria-hidden="true"></i>
                        Padrões mensuráveis
                    </span>
                    <span>
                        <i class="fas fa-balance-scale" aria-hidden="true"></i>
                        Decisões conscientes
                    </span>
                </div>
            </div>

            <div class="prisma-dashboard-hero__visual" aria-hidden="true">
                <div class="prisma-dashboard-hero__orb">
                    <i class="fas fa-chart-area"></i>
                </div>
                <span class="prisma-dashboard-hero__axis prisma-dashboard-hero__axis--one"></span>
                <span class="prisma-dashboard-hero__axis prisma-dashboard-hero__axis--two"></span>
                <strong>Informação primeiro.</strong>
                <small>Decisão depois.</small>
            </div>
        </section>

        <section class="prisma-dashboard-modules" aria-labelledby="dashboard-modulos">
            <div class="prisma-dashboard-section-heading">
                <div>
                    <span class="prisma-panel__kicker">Seu espaço</span>
                    <h2 id="dashboard-modulos">Recursos do sistema</h2>
                    <p>Acesse suas análises e acompanhe as próximas ferramentas da plataforma.</p>
                </div>
            </div>

            <div class="prisma-dashboard-grid">
                <article class="prisma-dashboard-card prisma-dashboard-card--active" data-dashboard-card="wheel">
                    <div class="prisma-dashboard-card__icon">
                        <i class="fas fa-circle-notch" aria-hidden="true"></i>
                    </div>
                    <span class="prisma-dashboard-card__status">Disponível</span>
                    <h3>Wheel</h3>
                    <p>
                        Consulte o histórico da ChillBet e acompanhe informações organizadas para
                        apoiar sua leitura estatística.
                    </p>
                    <a href="{{ route('chillbet.wheel') }}" class="prisma-dashboard-card__action">
                        Acessar análise
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>

                <article class="prisma-dashboard-card" data-dashboard-card="videos">
                    <div class="prisma-dashboard-card__icon">
                        <i class="fas fa-play-circle" aria-hidden="true"></i>
                    </div>
                    <span class="prisma-dashboard-card__status prisma-dashboard-card__status--future">
                        Em breve
                    </span>
                    <h3>Vídeos</h3>
                    <p>
                        Conteúdos para ajudar você a interpretar indicadores, recursos e análises
                        disponíveis na plataforma.
                    </p>
                    <span class="prisma-dashboard-card__action prisma-dashboard-card__action--disabled">
                        Conteúdo em preparação
                        <i class="fas fa-clock" aria-hidden="true"></i>
                    </span>
                </article>

                <article class="prisma-dashboard-card prisma-dashboard-card--active" data-dashboard-card="minha-assinatura">
                    <div class="prisma-dashboard-card__icon">
                        <i class="fas fa-id-card" aria-hidden="true"></i>
                    </div>
                    <span class="prisma-dashboard-card__status">Disponível</span>
                    <h3>Minha Assinatura</h3>
                    <p>
                        Consulte seus dados, acompanhe o período vigente, reveja o histórico de
                        acessos e mantenha sua senha atualizada.
                    </p>
                    <a href="{{ route('minha-assinatura.index') }}" class="prisma-dashboard-card__action">
                        Ver minha assinatura
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>
            </div>
        </section>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop
