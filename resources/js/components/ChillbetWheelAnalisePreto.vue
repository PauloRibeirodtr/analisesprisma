<template>
    <div class="col-md-3 col-sm-6 col-6 d-flex">
        <section class="card card-dark card-outline black-analysis w-100">
            <header class="card-header black-analysis__header">
                <h3 class="card-title font-weight-bold">Análise de Preto</h3>

                <span class="black-analysis__result-color" role="img" aria-label="Cor preta"></span>
            </header>

            <div class="card-body">
                <div class="black-analysis__current">
                    <strong>{{ formatarInteiro(analise.pretosIsoladosAtuais) }}</strong>
                    <span>pretos isolados atuais</span>
                </div>

                <dl class="black-analysis__metrics">
                    <div>
                        <dt>Máx. pretos sem duplo</dt>
                        <dd>{{ formatarNumero(analise.maximoPretosSemDuplo) }}</dd>
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
                        <dt>Média de pretos sem duplo</dt>
                        <dd>{{ formatarNumero(analise.mediaPretosSemDuplo) }}</dd>
                    </div>
                    <div>
                        <dt>Último preto</dt>
                        <dd>{{ formatarHora(analise.ultimoPretoEm) }}</dd>
                    </div>
                </dl>
            </div>

            <footer class="card-footer black-analysis__footer">
                <span>{{ analise.totalRegistros }} registros analisados</span>
                <span>{{ analise.totalPretosDuplos }} pretos duplos</span>
            </footer>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { analisarPreto } from '../analises/analisarPreto';
import { useChillbetWheelFeed } from '../composables/useChillbetWheelFeed';

const props = defineProps({
    quantidade: {
        type: Number,
        default: 2000,
    },
});

const { resultados } = useChillbetWheelFeed({ quantidade: props.quantidade });
const analise = computed(() => analisarPreto(resultados.value, props.quantidade));
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
.black-analysis {
    min-height: 100%;
}

.black-analysis__header,
.black-analysis__footer,
.black-analysis__metrics > div {
    align-items: center;
    display: flex;
    justify-content: space-between;
}

.black-analysis__header {
    min-height: 76px;
}

.black-analysis__result-color {
    background-color: #212529;
    border: 2px solid rgba(52, 58, 64, 0.45);
    border-radius: 50%;
    box-shadow: 0 0.2rem 0.5rem rgba(0, 0, 0, 0.15);
    display: block;
    flex: 0 0 48px;
    height: 48px;
    width: 48px;
}

.black-analysis__current {
    border-bottom: 1px solid #dee2e6;
    display: flex;
    flex-direction: column;
    padding-bottom: 0.75rem;
}

.black-analysis__current strong {
    color: #343a40;
    font-size: 2rem;
    line-height: 1;
}

.black-analysis__current span {
    color: #6c757d;
    margin-top: 0.3rem;
}

.black-analysis__metrics {
    margin: 0.5rem 0 0;
}

.black-analysis__metrics > div {
    border-bottom: 1px solid #edf0f2;
    gap: 1rem;
    padding: 0.5rem 0;
}

.black-analysis__metrics dt {
    color: #495057;
    font-size: 0.82rem;
    font-weight: 500;
}

.black-analysis__metrics dd {
    color: #343a40;
    font-size: 0.9rem;
    font-weight: 700;
    margin: 0;
    text-align: right;
    white-space: nowrap;
}

.black-analysis__footer {
    color: #6c757d;
    font-size: 0.75rem;
}
</style>
