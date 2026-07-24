<template>
    <div class="col-md-3 col-sm-6 col-6 d-flex">
        <section class="card card-primary card-outline blue-analysis w-100">
            <header class="card-header blue-analysis__header">
                <h3 class="card-title font-weight-bold">Análise de Azul</h3>

                <span class="blue-analysis__result-color" role="img" aria-label="Cor azul"></span>
            </header>

            <div class="card-body">
                <div class="blue-analysis__current">
                    <strong>{{ formatarInteiro(analise.azuisIsoladosAtuais) }}</strong>
                    <span>azuis isolados atuais</span>
                </div>

                <dl class="blue-analysis__metrics">
                    <div>
                        <dt>Máx. azuis sem duplo</dt>
                        <dd>{{ formatarNumero(analise.maximoAzuisSemDuplo) }}</dd>
                    </div>
                    <div>
                        <dt>Máx. minutos sem duplo</dt>
                        <dd>{{ formatarMinutos(analise.maximoMinutosSemDuplo) }}</dd>
                    </div>
                    <div>
                        <dt>Média de minutos sem duplo</dt>
                        <dd>{{ formatarMinutos(analise.mediaMinutosSemDuplo) }}</dd>
                    </div>
                    <div>
                        <dt>Média de azuis sem duplo</dt>
                        <dd>{{ formatarNumero(analise.mediaAzuisSemDuplo) }}</dd>
                    </div>
                    <div>
                        <dt>Último azul</dt>
                        <dd>{{ formatarHora(analise.ultimoAzulEm) }}</dd>
                    </div>
                </dl>
            </div>

            <footer class="card-footer blue-analysis__footer">
                <span>{{ analise.totalRegistros }} registros analisados</span>
                <span>{{ analise.totalAzuisDuplos }} azuis duplos</span>
            </footer>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { analisarAzul } from '../analises/analisarAzul';
import { useChillbetWheelFeed } from '../composables/useChillbetWheelFeed';

const props = defineProps({
    quantidade: {
        type: Number,
        default: 2000,
    },
});

const { resultados } = useChillbetWheelFeed({ quantidade: props.quantidade });
const analise = computed(() => analisarAzul(resultados.value, props.quantidade));
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
.blue-analysis {
    min-height: 100%;
}

.blue-analysis__header,
.blue-analysis__footer,
.blue-analysis__metrics > div {
    align-items: center;
    display: flex;
    justify-content: space-between;
}

.blue-analysis__header {
    min-height: 76px;
}

.blue-analysis__result-color {
    background-color: #007bff;
    border: 2px solid rgba(0, 123, 255, 0.45);
    border-radius: 50%;
    box-shadow: 0 0.2rem 0.5rem rgba(0, 0, 0, 0.15);
    display: block;
    flex: 0 0 48px;
    height: 48px;
    width: 48px;
}

.blue-analysis__current {
    border-bottom: 1px solid #dee2e6;
    display: flex;
    flex-direction: column;
    padding-bottom: 0.75rem;
}

.blue-analysis__current strong {
    color: #007bff;
    font-size: 2rem;
    line-height: 1;
}

.blue-analysis__current span {
    color: #6c757d;
    margin-top: 0.3rem;
}

.blue-analysis__metrics {
    margin: 0.5rem 0 0;
}

.blue-analysis__metrics > div {
    border-bottom: 1px solid #edf0f2;
    gap: 1rem;
    padding: 0.5rem 0;
}

.blue-analysis__metrics dt {
    color: #495057;
    font-size: 0.82rem;
    font-weight: 500;
}

.blue-analysis__metrics dd {
    color: #007bff;
    font-size: 0.9rem;
    font-weight: 700;
    margin: 0;
    text-align: right;
    white-space: nowrap;
}

.blue-analysis__footer {
    color: #6c757d;
    font-size: 0.75rem;
}
</style>
