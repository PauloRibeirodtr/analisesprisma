<template>
    <div>
        <section class="card card-outline card-secondary analysis-base-bar">
            <div class="card-body">
                <div class="analysis-base-bar__heading">
                    <strong>Base analisada</strong>
                    <span>{{ quantidadeSelecionada }} registros selecionados</span>
                </div>

                <div
                    class="analysis-base-bar__options"
                    role="group"
                    aria-label="Quantidade de registros analisados"
                >
                    <button
                        v-for="quantidade in quantidadesDisponiveis"
                        :key="quantidade"
                        type="button"
                        class="btn analysis-base-bar__button"
                        :class="quantidadeSelecionada === quantidade ? 'btn-primary' : 'btn-outline-primary'"
                        :aria-pressed="quantidadeSelecionada === quantidade"
                        :disabled="alterandoBase || sincronizando"
                        @click="selecionarQuantidade(quantidade)"
                    >
                        <span>{{ quantidade }}</span>
                        <small>registros</small>
                    </button>
                </div>
            </div>
        </section>

        <ChillbetWheelAnalisePadraoAoVivo :quantidade="quantidadeSelecionada" />

        <div class="row color-analysis-grid">
            <ChillbetWheelAnaliseAzul :quantidade="quantidadeSelecionada" />
            <ChillbetWheelAnaliseVermelho :quantidade="quantidadeSelecionada" />
            <ChillbetWheelAnalisePreto :quantidade="quantidadeSelecionada" />
            <ChillbetWheelAnaliseVerde :quantidade="quantidadeSelecionada" />
        </div>

        <ChillbetWheelHistorico :quantidade="quantidadeHistorico" />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useChillbetWheelFeed } from '../composables/useChillbetWheelFeed';
import ChillbetWheelAnaliseAzul from './ChillbetWheelAnaliseAzul.vue';
import ChillbetWheelAnalisePadraoAoVivo from './ChillbetWheelAnalisePadraoAoVivo.vue';
import ChillbetWheelAnalisePreto from './ChillbetWheelAnalisePreto.vue';
import ChillbetWheelAnaliseVerde from './ChillbetWheelAnaliseVerde.vue';
import ChillbetWheelAnaliseVermelho from './ChillbetWheelAnaliseVermelho.vue';
import ChillbetWheelHistorico from './ChillbetWheelHistorico.vue';

const props = defineProps({
    quantidadeAnalise: {
        type: Number,
        default: 2000,
    },
    quantidadeHistorico: {
        type: Number,
        default: 50,
    },
});

const quantidadesDisponiveis = [500, 1000, 2000];
const quantidadeSelecionada = ref(
    quantidadesDisponiveis.includes(props.quantidadeAnalise) ? props.quantidadeAnalise : 2000,
);
const alterandoBase = ref(false);
const { iniciar, sincronizando } = useChillbetWheelFeed({ iniciarAutomaticamente: false });

async function selecionarQuantidade(quantidade) {
    if (alterandoBase.value || sincronizando.value || quantidade === quantidadeSelecionada.value) {
        return;
    }

    const quantidadeAnterior = quantidadeSelecionada.value;
    quantidadeSelecionada.value = quantidade;
    alterandoBase.value = true;

    try {
        await iniciar(quantidade);
    } catch {
        quantidadeSelecionada.value = quantidadeAnterior;
    } finally {
        alterandoBase.value = false;
    }
}
</script>

<style scoped>
.color-analysis-grid {
    margin-bottom: 10px;
    row-gap: 10px;
}

.analysis-base-bar__heading {
    align-items: center;
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.analysis-base-bar__heading span {
    color: #6c757d;
    font-size: 0.8rem;
}

.analysis-base-bar__options {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    width: 100%;
}

.analysis-base-bar__button {
    align-items: center;
    display: flex;
    flex-direction: column;
    font-weight: 700;
    justify-content: center;
    min-width: 0;
    padding: 0.55rem 0.25rem;
}

.analysis-base-bar__button small {
    font-size: 0.7rem;
    font-weight: 400;
    line-height: 1.1;
}

@media (max-width: 575.98px) {
    .analysis-base-bar .card-body {
        padding: 0.75rem;
    }

    .analysis-base-bar__heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 0.2rem;
    }

    .analysis-base-bar__options {
        gap: 0.35rem;
    }
}
</style>
