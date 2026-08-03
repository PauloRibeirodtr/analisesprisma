<template>
    <section class="payment-windows" aria-labelledby="payment-windows-title">
        <header class="payment-windows__intro">
            <span class="payment-windows__intro-icon">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
            </span>
            <div>
                <span class="prisma-panel__kicker">Janelas de pagamento</span>
                <h2 id="payment-windows-title">Como funciona</h2>
                <p>
                    As janelas nascem das velas de 10x ou mais do Crash. Cada resultado
                    do Double dentro do intervalo representa um tiro até o branco.
                </p>
            </div>
            <div class="payment-windows__legend">
                <span><i class="payment-windows__dot payment-windows__dot--win"></i> Win: branco na janela</span>
                <span><i class="payment-windows__dot payment-windows__dot--loss"></i> Loss: janela sem branco</span>
            </div>
        </header>

        <div class="payment-windows__panel">
            <div class="payment-windows__heading">
                <div>
                    <span class="prisma-panel__kicker">Janelas da estratégia</span>
                    <h2>Blaze Double - Branco</h2>
                </div>
                <button
                    type="button"
                    class="payment-windows__refresh"
                    :disabled="atualizando"
                    @click="recarregar"
                >
                    <span v-if="painel?.atualizadoEm">
                        Atualizado: {{ formatarHorario(painel.atualizadoEm) }}
                    </span>
                    <i
                        class="fas fa-sync-alt"
                        :class="{ 'fa-spin': atualizando }"
                        aria-hidden="true"
                    ></i>
                    <span class="sr-only">Atualizar janelas</span>
                </button>
            </div>

            <div v-if="carregando && !painel" class="payment-windows__feedback">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Carregando...</span>
                </div>
                <strong>Carregando janelas de pagamento...</strong>
            </div>

            <div v-else-if="erro && !painel" class="payment-windows__feedback">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <strong>Painel temporariamente indisponível</strong>
                <span>{{ erro }}</span>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="recarregar">
                    Tentar novamente
                </button>
            </div>

            <template v-else>
                <div v-if="erro" class="alert alert-warning py-2" role="alert">
                    Exibindo a última atualização disponível. {{ erro }}
                </div>

                <div
                    v-if="painel?.recuperacao?.ativa"
                    class="payment-windows__recovery"
                    role="status"
                >
                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                    <div>
                        <strong>Plataforma em recuperação</strong>
                        <span>
                            Janelas e contagem pausadas desde
                            {{ formatarHorario(painel.recuperacao.iniciadaEm) }}.
                            {{ painel.recuperacao.brancosRecebidos }} de
                            {{ painel.recuperacao.brancosNecessarios }} brancos recebidos.
                        </span>
                    </div>
                </div>

                <section aria-labelledby="next-windows-title">
                    <h3 id="next-windows-title" class="payment-windows__section-title">
                        <i class="far fa-clock" aria-hidden="true"></i>
                        Próximas 3 janelas
                    </h3>

                    <div v-if="painel?.proximas.length" class="payment-windows__next-grid">
                        <article
                            v-for="(janela, indice) in painel.proximas"
                            :key="janela.id"
                            class="payment-window-card payment-window-card--next"
                            :class="`payment-window-card--${statusVisual(janela)}`"
                        >
                            <span class="payment-window-card__tag">Próxima {{ indice + 1 }}</span>
                            <h4>
                                <i class="far fa-calendar" aria-hidden="true"></i>
                                {{ formatarDia(janela.horarioAlvoEm) }}
                                <span>•</span>
                                {{ formatarHoraMinuto(janela.horarioAlvoEm) }}
                            </h4>
                            <p class="payment-window-card__range">
                                <i class="far fa-clock" aria-hidden="true"></i>
                                {{ formatarHorario(janela.inicioEm) }} até
                                {{ formatarHorario(janela.fimEm) }}
                            </p>
                            <div class="payment-window-card__status">
                                <i :class="iconeStatus(janela)" aria-hidden="true"></i>
                                {{ textoStatus(janela) }}
                            </div>
                            <div class="payment-window-card__shots">
                                <span>
                                    {{ statusVisual(janela) === 'cancelada_recuperacao'
                                        ? 'Tiros preservados no ciclo'
                                        : 'Tiros no ciclo atual' }}
                                </span>
                                <strong
                                    v-if="[
                                        'executando',
                                        'cancelada_recuperacao',
                                    ].includes(statusVisual(janela))"
                                >
                                    {{ janela.tirosNoCicloAtual ?? janela.tirosAcumuladosCiclo }}
                                </strong>
                                <strong v-else>—</strong>
                            </div>
                        </article>
                    </div>

                    <p v-else class="payment-windows__empty">
                        Nenhuma próxima janela foi sincronizada.
                    </p>
                </section>

                <section class="payment-windows__history" aria-labelledby="previous-windows-title">
                    <h3 id="previous-windows-title" class="payment-windows__section-title">
                        <i class="fas fa-chart-bar" aria-hidden="true"></i>
                        Últimas 5 janelas
                    </h3>

                    <div v-if="painel?.anteriores.length" class="payment-windows__history-grid">
                        <article
                            v-for="janela in painel.anteriores"
                            :key="janela.id"
                            class="payment-window-card payment-window-card--history"
                            :class="`payment-window-card--${janela.status}`"
                        >
                            <span class="payment-window-card__tag">
                                {{ janela.posicaoAnterior }} anterior
                            </span>
                            <h4>
                                {{ formatarDia(janela.horarioAlvoEm) }}
                                <span>•</span>
                                {{ formatarHoraMinuto(janela.horarioAlvoEm) }}
                            </h4>
                            <p class="payment-window-card__range">
                                {{ formatarHorario(janela.inicioEm) }} até
                                {{ formatarHorario(janela.fimEm) }}
                            </p>
                            <div class="payment-window-card__status">
                                <i
                                    :class="janela.status === 'win'
                                        ? 'fas fa-check'
                                        : 'fas fa-times'"
                                    aria-hidden="true"
                                ></i>
                                {{ janela.status.toUpperCase() }}
                            </div>
                            <strong class="payment-window-card__result">
                                <template v-if="janela.status === 'win'">
                                    {{ janela.tirosAcumuladosCiclo }}
                                    <small>tiros</small>
                                </template>
                                <template v-else>—</template>
                            </strong>
                            <small v-if="janela.fonteIndisponivel" class="payment-window-card__estimated">
                                Contagem estimada
                            </small>
                        </article>
                    </div>

                    <p v-else class="payment-windows__empty">
                        As janelas finalizadas aparecerão aqui.
                    </p>
                </section>

                <footer class="payment-windows__summary">
                    <div>
                        <i class="fas fa-bullseye" aria-hidden="true"></i>
                        <span><small>Ciclo atual</small><strong>{{ cicloAtualTexto }}</strong></span>
                    </div>
                    <div>
                        <i class="fas fa-sync-alt" aria-hidden="true"></i>
                        <span><small>Tiros no ciclo</small><strong>{{ painel?.cicloAtual?.totalTiros ?? 0 }}</strong></span>
                    </div>
                    <div>
                        <i class="fas fa-trophy" aria-hidden="true"></i>
                        <span>
                            <small>Último win</small>
                            <strong v-if="painel?.ultimoWin">
                                {{ formatarHoraMinuto(painel.ultimoWin.acertoEm) }}
                                <em>{{ painel.ultimoWin.totalTiros }} tiros</em>
                            </strong>
                            <strong v-else>—</strong>
                        </span>
                    </div>
                    <div>
                        <i class="fas fa-fire" aria-hidden="true"></i>
                        <span>
                            <small>Sequência atual</small>
                            <strong>{{ painel?.cicloAtual?.sequenciaLosses ?? 0 }} loss</strong>
                        </span>
                    </div>
                </footer>
            </template>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { useBlazeDoubleJanelas } from '../composables/useBlazeDoubleJanelas';

const {
    painel,
    carregando,
    atualizando,
    erro,
    atualizar,
} = useBlazeDoubleJanelas();

const FORMATADOR_DIA = new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    timeZone: 'America/Sao_Paulo',
});
const FORMATADOR_HORARIO = new Intl.DateTimeFormat('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
    timeZone: 'America/Sao_Paulo',
});
const FORMATADOR_HORA_MINUTO = new Intl.DateTimeFormat('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: 'America/Sao_Paulo',
});

const cicloAtualTexto = computed(() => {
    const ordem = painel.value?.cicloAtual?.ordemJanela;

    return ordem ? `${ordem}ª janela` : 'Aguardando';
});

function converterData(valor) {
    const data = new Date(valor);

    return Number.isNaN(data.getTime()) ? null : data;
}

function formatarHorario(valor) {
    const data = converterData(valor);

    return data ? FORMATADOR_HORARIO.format(data) : '--:--:--';
}

function formatarHoraMinuto(valor) {
    const data = converterData(valor);

    return data ? FORMATADOR_HORA_MINUTO.format(data) : '--:--';
}

function formatarDia(valor) {
    const data = converterData(valor);

    if (!data) {
        return 'Data inválida';
    }

    const hoje = FORMATADOR_DIA.format(new Date());
    const amanha = FORMATADOR_DIA.format(
        new Date(Date.now() + 24 * 60 * 60 * 1000),
    );
    const dia = FORMATADOR_DIA.format(data);

    if (dia === hoje) {
        return 'Hoje';
    }

    if (dia === amanha) {
        return 'Amanhã';
    }

    return dia;
}

function statusVisual(janela) {
    const agora = Date.now();
    const inicio = new Date(janela.inicioEm).getTime();
    const fim = new Date(janela.fimEm).getTime();

    if (
        agora >= inicio
        && agora <= fim
        && !['win', 'cancelada_recuperacao'].includes(janela.status)
    ) {
        return 'executando';
    }

    return janela.status;
}

function textoStatus(janela) {
    return {
        executando: 'Executando',
        win: 'Win',
        loss: 'Loss',
        agendada: 'Aguardando',
        cancelada_recuperacao: 'Cancelada · recuperação',
    }[statusVisual(janela)] || 'Aguardando';
}

function iconeStatus(janela) {
    return {
        executando: 'fas fa-play',
        win: 'fas fa-check',
        loss: 'fas fa-times',
        agendada: 'far fa-clock',
        cancelada_recuperacao: 'fas fa-pause',
    }[statusVisual(janela)] || 'far fa-clock';
}

function recarregar() {
    void atualizar().catch(() => {});
}
</script>

<style scoped>
.payment-windows {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.payment-windows__intro,
.payment-windows__panel {
    border: 1px solid var(--prisma-border);
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 12px 30px rgba(24, 38, 100, 0.07);
}

.payment-windows__intro {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 1.2rem;
    padding: 1.25rem 1.4rem;
}

.payment-windows__intro-icon {
    display: grid;
    width: 52px;
    height: 52px;
    place-items: center;
    border-radius: 14px;
    background: linear-gradient(145deg, #3359ef, #735af6);
    box-shadow: 0 10px 20px rgba(51, 89, 239, 0.25);
    color: #fff;
    font-size: 1.35rem;
}

.payment-windows__intro h2,
.payment-windows__heading h2 {
    margin: 0.15rem 0 0.25rem;
    color: var(--prisma-navy);
    font-size: 1.2rem;
    font-weight: 800;
}

.payment-windows__intro p {
    margin: 0;
    color: var(--prisma-muted);
    font-size: 0.84rem;
}

.payment-windows__legend {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    color: var(--prisma-navy);
    font-size: 0.78rem;
}

.payment-windows__legend span {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.payment-windows__dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
}

.payment-windows__dot--win {
    background: #24b64b;
    box-shadow: 0 0 0 6px rgba(36, 182, 75, 0.09);
}

.payment-windows__dot--loss {
    background: #ef3f52;
    box-shadow: 0 0 0 6px rgba(239, 63, 82, 0.09);
}

.payment-windows__panel {
    padding: 1.35rem;
}

.payment-windows__heading,
.payment-windows__summary,
.payment-windows__refresh {
    display: flex;
    align-items: center;
}

.payment-windows__heading {
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9edf5;
}

.payment-windows__refresh {
    gap: 0.45rem;
    border: 0;
    background: transparent;
    color: var(--prisma-muted);
    font-size: 0.73rem;
}

.payment-windows__section-title {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin: 1rem 0 0.85rem;
    color: var(--prisma-navy);
    font-size: 0.95rem;
    font-weight: 800;
}

.payment-windows__section-title i {
    color: var(--prisma-blue);
}

.payment-windows__recovery {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    margin-top: 1rem;
    padding: 0.9rem 1rem;
    border: 1px solid #f1c36b;
    border-radius: 12px;
    background: #fff8e8;
    color: #80540a;
}

.payment-windows__recovery > i {
    font-size: 1.3rem;
}

.payment-windows__recovery strong,
.payment-windows__recovery span {
    display: block;
}

.payment-windows__recovery span {
    margin-top: 0.15rem;
    font-size: 0.76rem;
}

.payment-windows__next-grid,
.payment-windows__history-grid {
    display: grid;
    gap: 0.85rem;
}

.payment-windows__next-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.payment-windows__history-grid {
    grid-template-columns: repeat(5, minmax(0, 1fr));
}

.payment-window-card {
    position: relative;
    overflow: hidden;
    border: 1px solid #dfe5f0;
    border-radius: 14px;
    background: linear-gradient(145deg, #fff, #fbfcff);
}

.payment-window-card::after {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    height: 3px;
    background: #b8c2d5;
    content: "";
}

.payment-window-card--next {
    min-height: 220px;
    padding: 1rem 1.05rem;
}

.payment-window-card--history {
    min-height: 185px;
    padding: 0.85rem;
}

.payment-window-card--executando {
    border-color: #75a5ff;
    box-shadow: 0 8px 18px rgba(36, 102, 230, 0.1);
}

.payment-window-card--executando::after {
    background: #2466e6;
}

.payment-window-card--win::after {
    background: #20b449;
}

.payment-window-card--loss::after {
    background: #ef3f52;
}

.payment-window-card--cancelada_recuperacao::after {
    background: #d99a24;
}

.payment-window-card__tag {
    display: inline-flex;
    padding: 0.25rem 0.5rem;
    border-radius: 5px;
    background: #edf3ff;
    color: #075be8;
    font-size: 0.62rem;
    font-weight: 800;
    text-transform: uppercase;
}

.payment-window-card h4 {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin: 0.9rem 0 0.4rem;
    color: var(--prisma-navy);
    font-size: 1rem;
    font-weight: 800;
}

.payment-window-card__range {
    margin: 0;
    color: var(--prisma-muted);
    font-size: 0.72rem;
}

.payment-window-card__status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 0.85rem;
    padding: 0.45rem;
    border: 1px solid #b9c4d5;
    border-radius: 999px;
    background: #f1f4f8;
    color: #596980;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
}

.payment-window-card--executando .payment-window-card__status {
    border-color: #73a4ff;
    background: #edf5ff;
    color: #075be8;
}

.payment-window-card--win .payment-window-card__status {
    border-color: #85dfa0;
    background: #e5f9ea;
    color: #119437;
}

.payment-window-card--loss .payment-window-card__status {
    border-color: #ff9ba7;
    background: #fff0f2;
    color: #db243b;
}

.payment-window-card--cancelada_recuperacao .payment-window-card__status {
    border-color: #f1c36b;
    background: #fff8e8;
    color: #80540a;
}

.payment-window-card__shots {
    margin-top: 0.9rem;
    text-align: center;
}

.payment-window-card__shots span,
.payment-window-card__shots strong {
    display: block;
}

.payment-window-card__shots span {
    color: var(--prisma-muted);
    font-size: 0.65rem;
}

.payment-window-card__shots strong {
    margin-top: 0.15rem;
    color: var(--prisma-navy);
    font-size: 1.18rem;
}

.payment-window-card__result {
    display: block;
    margin-top: 0.65rem;
    color: #12a33b;
    font-size: 1.25rem;
    text-align: center;
}

.payment-window-card__result small {
    font-size: 0.65rem;
}

.payment-window-card__estimated {
    display: block;
    margin-top: 0.25rem;
    color: #c8790b;
    font-size: 0.58rem;
    text-align: center;
}

.payment-windows__history {
    margin-top: 1rem;
    padding-top: 0.1rem;
    border-top: 1px solid #e9edf5;
}

.payment-windows__summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9edf5;
}

.payment-windows__summary > div {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.7rem;
    min-height: 48px;
    padding: 0 1rem;
    border-right: 1px solid #e1e6ef;
}

.payment-windows__summary > div:last-child {
    border-right: 0;
}

.payment-windows__summary i {
    color: var(--prisma-blue);
    font-size: 1.35rem;
}

.payment-windows__summary small,
.payment-windows__summary strong {
    display: block;
}

.payment-windows__summary small {
    color: var(--prisma-blue);
    font-size: 0.58rem;
    font-weight: 800;
    text-transform: uppercase;
}

.payment-windows__summary strong {
    color: var(--prisma-navy);
    font-size: 1rem;
}

.payment-windows__summary em {
    margin-left: 0.3rem;
    color: var(--prisma-muted);
    font-size: 0.6rem;
    font-style: normal;
}

.payment-windows__feedback,
.payment-windows__empty {
    color: var(--prisma-muted);
    text-align: center;
}

.payment-windows__feedback {
    display: flex;
    min-height: 300px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.7rem;
}

.payment-windows__empty {
    padding: 1.5rem;
    border: 1px dashed #dce3ee;
    border-radius: 12px;
}

@media (max-width: 1199.98px) {
    .payment-windows__history-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 991.98px) {
    .payment-windows__intro {
        grid-template-columns: auto minmax(0, 1fr);
    }

    .payment-windows__legend {
        grid-column: 1 / -1;
        flex-direction: row;
        flex-wrap: wrap;
        padding-left: 4.5rem;
    }

    .payment-windows__summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .payment-windows__summary > div:nth-child(2) {
        border-right: 0;
    }
}

@media (max-width: 767.98px) {
    .payment-windows__next-grid,
    .payment-windows__history-grid {
        grid-template-columns: 1fr;
    }

    .payment-windows__heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .payment-windows__refresh {
        padding: 0;
    }
}

@media (max-width: 575.98px) {
    .payment-windows__intro {
        grid-template-columns: 1fr;
    }

    .payment-windows__intro-icon {
        width: 44px;
        height: 44px;
    }

    .payment-windows__legend {
        grid-column: auto;
        padding-left: 0;
    }

    .payment-windows__panel {
        padding: 1rem;
    }

    .payment-windows__summary {
        grid-template-columns: 1fr;
    }

    .payment-windows__summary > div {
        justify-content: flex-start;
        border-right: 0;
        border-bottom: 1px solid #e1e6ef;
    }

    .payment-windows__summary > div:last-child {
        border-bottom: 0;
    }
}
</style>
