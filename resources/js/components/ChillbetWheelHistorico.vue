<template>
    <section class="card card-primary card-outline chillbet-history">
        <header class="card-header chillbet-history__header">
            <h3 class="card-title mb-0">
                Últimos {{ quantidade }} resultados da Wheel ChillBet
            </h3>

            <span
                class="badge chillbet-history__status"
                :class="socketConectado ? 'badge-success' : 'badge-secondary'"
            >
                <span class="chillbet-history__status-dot" aria-hidden="true"></span>
                {{ socketConectado ? 'Tempo real conectado' : 'Tempo real desconectado' }}
            </span>
        </header>

        <div class="card-body">
            <div class="chillbet-history__toolbar mb-3">
                <div class="text-muted">
                    <strong>{{ resultadosExibidos.length }}</strong> resultados exibidos
                    <span v-if="ultimaAtualizacao">
                        · Atualizado às {{ formatarUltimaAtualizacao(ultimaAtualizacao) }}
                    </span>
                </div>

                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    :disabled="sincronizando"
                    @click="atualizarHistorico"
                >
                    <i class="fas fa-sync-alt mr-1" :class="{ 'fa-spin': sincronizando }"></i>
                    {{ sincronizando ? 'Sincronizando' : 'Sincronizar' }}
                </button>
            </div>

            <div v-if="socketErro" class="alert alert-warning py-2" role="alert">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Histórico carregado, mas o tempo real está desconectado: {{ socketErro }}
            </div>

            <div v-if="erro && resultadosExibidos.length > 0" class="alert alert-warning py-2" role="alert">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Não foi possível ressincronizar o histórico: {{ erro }}
            </div>

            <div v-if="carregando && resultadosExibidos.length === 0" class="chillbet-history__feedback">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Carregando...</span>
                </div>
                <span>Carregando histórico ChillBet...</span>
            </div>

            <div v-else-if="erro && resultadosExibidos.length === 0" class="chillbet-history__feedback">
                <div class="alert alert-danger mb-0" role="alert">
                    <h4 class="alert-heading h6">Histórico indisponível</h4>
                    <p class="mb-2">{{ erro }}</p>
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="atualizarHistorico">
                        Tentar novamente
                    </button>
                </div>
            </div>

            <div v-else-if="resultadosExibidos.length === 0" class="text-center text-muted py-4">
                Nenhum resultado foi encontrado.
            </div>

            <div v-else class="chillbet-history__grid" aria-label="Últimos resultados da Wheel ChillBet">
                <figure
                    v-for="resultado in resultadosExibidos"
                    :key="resultado.gameId"
                    class="chillbet-history__result"
                >
                    <img
                        class="chillbet-history__image"
                        :src="imagemCor(resultado.cor)"
                        :alt="nomeCor(resultado.cor)"
                        :title="`${nomeCor(resultado.cor)} · Jogo ${resultado.gameId}`"
                    >
                    <figcaption>
                        {{ formatarHorario(resultado.data) }}
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { useChillbetWheelFeed } from '../composables/useChillbetWheelFeed';

const props = defineProps({
    quantidade: {
        type: Number,
        default: 50,
    },
});

const {
    resultados,
    carregando,
    sincronizando,
    erro,
    socketConectado,
    socketErro,
    ultimaAtualizacao,
    sincronizar,
} = useChillbetWheelFeed({ quantidade: props.quantidade });

const resultadosExibidos = computed(() => resultados.value.slice(0, props.quantidade));

function nomeCor(cor) {
    return {
        BLACK: 'Preto',
        RED: 'Vermelho',
        BLUE: 'Azul',
        GREEN: 'Verde',
    }[cor] || cor;
}

function imagemCor(cor) {
    return `/img/chillbet/${String(cor).toLowerCase()}.png`;
}

function converterData(valor) {
    if (!valor) {
        return null;
    }

    const valorIso = String(valor).replace(' ', 'T');
    const possuiFuso = /(Z|[+-]\d{2}:?\d{2})$/i.test(valorIso);
    const data = new Date(possuiFuso ? valorIso : `${valorIso}-03:00`);

    return Number.isNaN(data.getTime()) ? null : data;
}

function formatarHorario(valor) {
    const data = converterData(valor);

    if (!data) {
        return '--:--:--';
    }

    return data.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        timeZone: 'America/Sao_Paulo',
        hour12: false,
    });
}

function formatarUltimaAtualizacao(data) {
    return data.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });
}

function atualizarHistorico() {
    void sincronizar({ mostrarCarregamento: resultadosExibidos.value.length === 0 }).catch(() => {});
}
</script>

<style scoped>
.chillbet-history__header,
.chillbet-history__toolbar,
.chillbet-history__feedback {
    align-items: center;
    display: flex;
}

.chillbet-history__header,
.chillbet-history__toolbar {
    justify-content: space-between;
}

.chillbet-history__status {
    align-items: center;
    display: inline-flex;
    font-size: 0.78rem;
    gap: 0.35rem;
    padding: 0.4rem 0.55rem;
}

.chillbet-history__status-dot {
    background: currentColor;
    border-radius: 50%;
    display: inline-block;
    height: 0.45rem;
    width: 0.45rem;
}

.chillbet-history__feedback {
    flex-direction: column;
    gap: 0.75rem;
    justify-content: center;
    min-height: 150px;
}

.chillbet-history__grid {
    display: grid;
    gap: 0.65rem;
    grid-template-columns: repeat(auto-fill, minmax(62px, 1fr));
}

.chillbet-history__result {
    align-items: center;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin: 0;
    min-width: 0;
}

.chillbet-history__image {
    border: 1px solid rgba(0, 0, 0, 0.25);
    border-radius: 0.25rem;
    box-shadow: inset 0 0 0 3px rgba(255, 255, 255, 0.2);
    height: 52px;
    object-fit: cover;
    width: 52px;
}

.chillbet-history__result figcaption {
    color: #6c757d;
    font-size: 0.7rem;
    white-space: nowrap;
}

@media (max-width: 575.98px) {
    .chillbet-history__header,
    .chillbet-history__toolbar {
        align-items: flex-start;
        flex-direction: column;
        gap: 0.75rem;
    }

    .chillbet-history__grid {
        grid-template-columns: repeat(auto-fill, minmax(56px, 1fr));
    }
}
</style>
