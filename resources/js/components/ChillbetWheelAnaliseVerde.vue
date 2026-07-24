<template>
    <div class="col-md-3 col-sm-6 col-6 d-flex">
        <section class="card card-success card-outline green-analysis w-100">
            <header class="card-header green-analysis__header">
                <h3 class="card-title font-weight-bold">Análise de Verde</h3>

                <span class="green-analysis__result-color" role="img" aria-label="Cor verde"></span>
            </header>

            <div class="card-body">
                <div class="green-analysis__current">
                    <strong>{{ formatarInteiro(analise.totalVerdes) }}</strong>
                    <span>verdes encontrados</span>
                </div>

                <dl class="green-analysis__metrics">
                    <div>
                        <dt>Máx. distância em rodadas</dt>
                        <dd>{{ formatarNumero(analise.maximaDistanciaRodadas) }}</dd>
                    </div>
                    <div>
                        <dt>Média de distância em rodadas</dt>
                        <dd>{{ formatarNumero(analise.mediaDistanciaRodadas) }}</dd>
                    </div>
                    <div>
                        <dt>Máx. distância em minutos</dt>
                        <dd>{{ formatarMinutos(analise.maximaDistanciaMinutos) }}</dd>
                    </div>
                    <div>
                        <dt>Média de distância em minutos</dt>
                        <dd>{{ formatarMinutos(analise.mediaDistanciaMinutos) }}</dd>
                    </div>
                    <div>
                        <dt>Último verde</dt>
                        <dd>{{ formatarHora(analise.ultimoVerdeEm) }}</dd>
                    </div>
                </dl>
            </div>

            <footer class="card-footer green-analysis__footer">
                <span>{{ analise.totalRegistros }} registros analisados</span>
                <span>{{ analise.totalIntervalos }} intervalos</span>
            </footer>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { analisarVerde } from '../analises/analisarVerde';
import { useChillbetWheelFeed } from '../composables/useChillbetWheelFeed';

const props = defineProps({
    quantidade: {
        type: Number,
        default: 2000,
    },
});

const { resultados } = useChillbetWheelFeed({ quantidade: props.quantidade });
const analise = computed(() => analisarVerde(resultados.value, props.quantidade));
const formatadorDecimal = new Intl.NumberFormat('pt-BR', {
    maximumFractionDigits: 1,
    minimumFractionDigits: 0,
});

function formatarInteiro(valor) {
    return new Intl.NumberFormat('pt-BR').format(valor);
}

function formatarNumero(valor) {
    return valor === null ? '—' : formatadorDecimal.format(valor);
}

function formatarMinutos(valor) {
    return valor === null ? '—' : `${formatadorDecimal.format(valor)} min`;
}

function formatarHora(data) {
    if (!data) {
        return '—';
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
.green-analysis {
    min-height: 100%;
}

.green-analysis__header,
.green-analysis__footer,
.green-analysis__metrics > div {
    align-items: center;
    display: flex;
    justify-content: space-between;
}

.green-analysis__header {
    min-height: 76px;
}

.green-analysis__result-color {
    background-color: #28a745;
    border: 2px solid rgba(40, 167, 69, 0.45);
    border-radius: 50%;
    box-shadow: 0 0.2rem 0.5rem rgba(0, 0, 0, 0.15);
    display: block;
    flex: 0 0 48px;
    height: 48px;
    width: 48px;
}

.green-analysis__current {
    border-bottom: 1px solid #dee2e6;
    display: flex;
    flex-direction: column;
    padding-bottom: 0.75rem;
}

.green-analysis__current strong {
    color: #28a745;
    font-size: 2rem;
    line-height: 1;
}

.green-analysis__current span {
    color: #6c757d;
    margin-top: 0.3rem;
}

.green-analysis__metrics {
    margin: 0.5rem 0 0;
}

.green-analysis__metrics > div {
    border-bottom: 1px solid #edf0f2;
    gap: 1rem;
    padding: 0.5rem 0;
}

.green-analysis__metrics dt {
    color: #495057;
    font-size: 0.82rem;
    font-weight: 500;
}

.green-analysis__metrics dd {
    color: #28a745;
    font-size: 0.9rem;
    font-weight: 700;
    margin: 0;
    text-align: right;
    white-space: nowrap;
}

.green-analysis__footer {
    color: #6c757d;
    font-size: 0.75rem;
}
</style>
