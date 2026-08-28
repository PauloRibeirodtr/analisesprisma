<template>
    <section class="card card-primary card-outline live-pattern">
        <header class="card-header live-pattern__header">
            <div class="live-pattern__title">
                <span class="live-pattern__title-icon" aria-hidden="true">
                    <i class="fas fa-chart-line"></i>
                </span>
                <div>
                    <h3 class="card-title font-weight-bold">Análise de Padrão ao Vivo</h3>
                    <p>As últimas {{ tamanhoPadraoSelecionado }} pedras comparadas com ocorrências reais da base selecionada.</p>
                </div>
            </div>

            <span
                class="badge live-pattern__status"
                :class="socketConectado ? 'badge-success' : 'badge-secondary'"
            >
                <span class="live-pattern__status-dot" aria-hidden="true"></span>
                {{ socketConectado ? 'Atualizado em tempo real' : 'Tempo real desconectado' }}
            </span>
        </header>

        <div class="live-pattern__size-selector">
            <div class="live-pattern__size-heading">
                <strong>Tamanho do padrão analisado</strong>
                <span>{{ tamanhoPadraoSelecionado }} pedras selecionadas</span>
            </div>

            <div
                class="live-pattern__size-options"
                role="group"
                aria-label="Quantidade de pedras do padrão"
            >
                <button
                    v-for="tamanho in tamanhosPadraoDisponiveis"
                    :key="tamanho"
                    type="button"
                    class="btn live-pattern__size-button"
                    :class="{ 'live-pattern__size-button--active': tamanhoPadraoSelecionado === tamanho }"
                    :aria-pressed="tamanhoPadraoSelecionado === tamanho"
                    @click="tamanhoPadraoSelecionado = tamanho"
                >
                    <span>{{ tamanho }}</span>
                    <small>pedras</small>
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <section class="col-lg-4 live-pattern__section">
                    <h4>Padrão atual (últimas {{ tamanhoPadraoSelecionado }} pedras)</h4>

                    <div v-if="analise.padraoCompleto" class="live-pattern__stones">
                        <span
                            v-for="(cor, indice) in analise.padraoAtual"
                            :key="`${indice}-${cor}`"
                            class="live-pattern__stone"
                            :class="classeCor(cor)"
                            role="img"
                            :aria-label="nomeCor(cor)"
                        ></span>
                    </div>
                    <p v-else class="text-muted mb-3">
                        Aguardando {{ tamanhoPadraoSelecionado }} resultados para formar o padrão.
                    </p>

                    <div class="live-pattern__summary">
                        <div>
                            <i class="fas fa-bullseye text-primary" aria-hidden="true"></i>
                            <strong>{{ analise.totalOcorrencias }}</strong>
                            <span>ocorrências</span>
                        </div>
                        <div>
                            <i class="fas fa-database text-primary" aria-hidden="true"></i>
                            <strong>{{ analise.totalRegistros }}</strong>
                            <span>registros consultados</span>
                        </div>
                    </div>
                </section>

                <section class="col-lg-8 live-pattern__section live-pattern__distribution">
                    <h4>Distribuição histórica da próxima cor</h4>

                    <div
                        v-for="item in analise.distribuicao"
                        :key="item.cor"
                        class="live-pattern__distribution-row"
                    >
                        <span class="live-pattern__color-label">
                            <span class="live-pattern__color-dot" :class="classeCor(item.cor)" aria-hidden="true"></span>
                            {{ nomeCor(item.cor) }}
                        </span>
                        <div
                            class="progress live-pattern__progress"
                            role="progressbar"
                            :aria-label="`Percentual de ${nomeCor(item.cor)}`"
                            :aria-valuenow="item.percentual"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div
                                class="progress-bar"
                                :class="classeBarra(item.cor)"
                                :style="{ width: `${item.percentual}%` }"
                            ></div>
                        </div>
                        <strong>{{ formatarPercentual(item.percentual) }}</strong>
                        <small>{{ item.total }}x</small>
                    </div>
                </section>
            </div>

            <section class="live-pattern__history" aria-labelledby="live-pattern-history-title">
                <div class="live-pattern__history-heading">
                    <h4 id="live-pattern-history-title">Últimos resultados após esse padrão</h4>
                    <span>Até 5 ocorrências mais recentes</span>
                </div>

                <div
                    v-if="analise.resultadosPosPadrao.length"
                    class="live-pattern__history-results"
                    aria-label="Resultados mais recentes após o padrão atual"
                >
                    <figure
                        v-for="(resultado, indice) in analise.resultadosPosPadrao"
                        :key="chaveResultado(resultado, indice)"
                        class="live-pattern__history-result"
                    >
                        <span
                            class="live-pattern__history-stone"
                            :class="classeCor(resultado.cor)"
                            role="img"
                            :aria-label="`Resultado pós-padrão: ${nomeCor(resultado.cor)}`"
                        ></span>
                        <figcaption>{{ formatarHorario(resultado.data) }}</figcaption>
                    </figure>
                </div>

                <p v-else class="live-pattern__history-empty">
                    Nenhuma ocorrência anterior completa encontrada na base selecionada.
                </p>
            </section>

            <p class="live-pattern__disclaimer">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                Esta é uma análise estatística histórica. Os percentuais não representam sinal nem garantia de resultado futuro.
            </p>
        </div>
    </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import { analisarPadraoAoVivo } from '../analises/analisarPadraoAoVivo';
import { useChillbetWheelFeed } from '../composables/useChillbetWheelFeed';

const props = defineProps({
    quantidade: {
        type: Number,
        default: 2000,
    },
});

const tamanhosPadraoDisponiveis = [3, 4, 5];
const tamanhoPadraoSelecionado = ref(4);
const { resultados, socketConectado } = useChillbetWheelFeed({ quantidade: props.quantidade });
const analise = computed(() => analisarPadraoAoVivo(
    resultados.value,
    props.quantidade,
    tamanhoPadraoSelecionado.value,
));

function chaveResultado(resultado, indice) {
    return `historico-${resultado.gameId || resultado.id || resultado.data || indice}-${indice}`;
}

function nomeCor(cor) {
    return {
        BLACK: 'Preto',
        BLUE: 'Azul',
        RED: 'Vermelho',
        GREEN: 'Verde',
    }[cor] || cor;
}

function classeCor(cor) {
    return `live-pattern__color--${String(cor).toLowerCase()}`;
}

function classeBarra(cor) {
    return `live-pattern__bar--${String(cor).toLowerCase()}`;
}

function formatarPercentual(percentual) {
    return `${new Intl.NumberFormat('pt-BR', {
        maximumFractionDigits: 1,
        minimumFractionDigits: 0,
    }).format(percentual)}%`;
}

function formatarHorario(valor) {
    if (!valor) {
        return '--:--';
    }

    const valorIso = String(valor).replace(' ', 'T');
    const possuiFuso = /(Z|[+-]\d{2}:?\d{2})$/i.test(valorIso);
    const data = new Date(possuiFuso ? valorIso : `${valorIso}-03:00`);

    if (Number.isNaN(data.getTime())) {
        return String(valor).split(' ')[1]?.slice(0, 5) || '--:--';
    }

    return data.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'America/Sao_Paulo',
        hour12: false,
    });
}
</script>

<style scoped>
.live-pattern {
    border-top-width: 3px;
    box-shadow: 0 0.2rem 0.75rem rgba(0, 0, 0, 0.08);
}

.live-pattern__header,
.live-pattern__title,
.live-pattern__status,
.live-pattern__color-label,
.live-pattern__disclaimer {
    align-items: center;
    display: flex;
}

.live-pattern__header {
    gap: 1rem;
    justify-content: space-between;
}

.live-pattern__title {
    gap: 0.75rem;
}

.live-pattern__title .card-title {
    float: none;
}

.live-pattern__title p {
    color: #6c757d;
    font-size: 0.78rem;
    margin: 0.2rem 0 0;
}

.live-pattern__title-icon {
    align-items: center;
    background: #4c22ff;
    border-radius: 50%;
    color: #fff;
    display: inline-flex;
    flex: 0 0 auto;
    justify-content: center;
}

.live-pattern__title-icon {
    height: 36px;
    width: 36px;
}

.live-pattern__status {
    gap: 0.35rem;
    white-space: nowrap;
}

.live-pattern__status-dot {
    background: currentColor;
    border-radius: 50%;
    height: 0.4rem;
    width: 0.4rem;
}

.live-pattern__size-selector {
    background: #faf8ff;
    border-bottom: 1px solid #e5ddff;
    padding: 0.75rem 1.25rem 1rem;
}

.live-pattern__size-heading {
    align-items: center;
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.65rem;
}

.live-pattern__size-heading strong {
    color: #5f35a5;
    font-size: 0.82rem;
}

.live-pattern__size-heading span {
    color: #7d6b9d;
    font-size: 0.72rem;
}

.live-pattern__size-options {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.live-pattern__size-button {
    align-items: center;
    background: #fff;
    border: 1px solid #7b4bc4;
    color: #6938b4;
    display: flex;
    flex-direction: column;
    font-weight: 700;
    justify-content: center;
    min-width: 0;
    padding: 0.45rem 0.25rem;
}

.live-pattern__size-button:hover,
.live-pattern__size-button:focus,
.live-pattern__size-button--active {
    background: #6f42c1;
    border-color: #6f42c1;
    color: #fff;
}

.live-pattern__size-button small {
    font-size: 0.68rem;
    font-weight: 400;
    line-height: 1.1;
}

.live-pattern__section h4 {
    font-size: 0.85rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.live-pattern__stones {
    display: flex;
    flex-wrap: nowrap;
    gap: clamp(0.35rem, 1vw, 0.8rem);
    margin-bottom: 1.25rem;
}

.live-pattern__stone {
    border: 2px solid rgba(255, 255, 255, 0.85);
    border-radius: 50%;
    box-shadow: 0 0.15rem 0.4rem rgba(0, 0, 0, 0.2);
    flex: 0 1 34px;
    height: 34px;
    min-width: 24px;
}

.live-pattern__color--black,
.live-pattern__bar--black {
    background-color: #212529;
}

.live-pattern__color--blue,
.live-pattern__bar--blue {
    background-color: #3f16ff;
}

.live-pattern__color--red,
.live-pattern__bar--red {
    background-color: #f00017;
}

.live-pattern__color--green,
.live-pattern__bar--green {
    background-color: #00b85a;
}

.live-pattern__summary {
    border-top: 1px solid #dee2e6;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    padding-top: 1rem;
}

.live-pattern__summary > div {
    align-items: center;
    display: grid;
    gap: 0.1rem 0.4rem;
    grid-template-columns: auto 1fr;
    padding: 0 0.75rem;
}

.live-pattern__summary > div + div {
    border-left: 1px solid #dee2e6;
}

.live-pattern__summary i {
    grid-row: 1 / 3;
}

.live-pattern__summary strong {
    font-size: 1.15rem;
}

.live-pattern__summary span {
    color: #6c757d;
    font-size: 0.68rem;
}

.live-pattern__distribution {
    border-left: 1px solid #dee2e6;
}

.live-pattern__distribution-row {
    align-items: center;
    display: grid;
    gap: 0.5rem;
    grid-template-columns: 75px minmax(60px, 1fr) 48px 26px;
    margin-bottom: 0.65rem;
}

.live-pattern__color-label {
    font-size: 0.78rem;
    gap: 0.4rem;
}

.live-pattern__color-dot {
    border-radius: 50%;
    flex: 0 0 10px;
    height: 10px;
    width: 10px;
}

.live-pattern__progress {
    background-color: #edf0f2;
    border-radius: 999px;
    height: 11px;
}

.live-pattern__progress .progress-bar {
    border-radius: 999px;
}

.live-pattern__distribution-row > strong,
.live-pattern__distribution-row > small {
    font-size: 0.72rem;
    text-align: right;
}

.live-pattern__distribution-row > small {
    color: #6c757d;
}

.live-pattern__history {
    align-items: center;
    border-top: 1px solid #dee2e6;
    display: grid;
    gap: 1rem;
    grid-template-columns: minmax(190px, 0.35fr) minmax(0, 1fr);
    margin-top: 1rem;
    padding-top: 1rem;
}

.live-pattern__history-heading h4 {
    font-size: 0.85rem;
    font-weight: 700;
    margin: 0;
}

.live-pattern__history-heading span {
    color: #6c757d;
    font-size: 0.7rem;
}

.live-pattern__history-results {
    display: flex;
    flex-wrap: wrap;
    gap: clamp(0.7rem, 2vw, 1.5rem);
}

.live-pattern__history-result {
    align-items: center;
    display: flex;
    flex: 0 0 38px;
    flex-direction: column;
    gap: 0.3rem;
    margin: 0;
}

.live-pattern__history-stone {
    border: 2px solid rgba(255, 255, 255, 0.85);
    border-radius: 50%;
    box-shadow: 0 0.15rem 0.4rem rgba(0, 0, 0, 0.2);
    display: block;
    height: 38px;
    width: 38px;
}

.live-pattern__history-result figcaption {
    color: #495057;
    font-size: 0.68rem;
    line-height: 1;
    white-space: nowrap;
}

.live-pattern__history-empty {
    color: #6c757d;
    font-size: 0.76rem;
    margin: 0;
}

.live-pattern__disclaimer {
    background: #f8f9fa;
    border-radius: 0.25rem;
    color: #6c757d;
    font-size: 0.72rem;
    gap: 0.4rem;
    margin: 1rem 0 0;
    padding: 0.5rem 0.65rem;
}

@media (max-width: 991.98px) {
    .live-pattern__distribution {
        margin-top: 1.25rem;
    }

    .live-pattern__distribution {
        border-left: 0;
    }

    .live-pattern__history {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 575.98px) {
    .live-pattern__header {
        align-items: flex-start;
        flex-direction: column;
    }

    .live-pattern__title {
        align-items: flex-start;
    }

    .live-pattern__size-selector {
        padding: 0.7rem 0.75rem 0.85rem;
    }

    .live-pattern__size-heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 0.15rem;
    }

    .live-pattern__size-options {
        gap: 0.35rem;
    }

    .live-pattern__distribution-row {
        gap: 0.3rem;
        grid-template-columns: 65px minmax(45px, 1fr) 44px 22px;
    }

    .live-pattern__summary > div {
        padding: 0 0.4rem;
    }
}
</style>
