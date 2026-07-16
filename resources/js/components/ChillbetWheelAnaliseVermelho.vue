<template>
    <div class="col-md-3 col-sm-6 col-6 d-flex">
        <section class="card card-danger card-outline red-analysis w-100">
            <header class="card-header red-analysis__header">
                <h3 class="card-title font-weight-bold">Análise de Vermelho</h3>

                <span class="red-analysis__result-color" role="img" aria-label="Cor vermelha"></span>
            </header>

            <div class="card-body">
                <div class="red-analysis__current">
                    <strong>{{ formatarInteiro(analise.vermelhosIsoladosAtuais) }}</strong>
                    <span>vermelhos isolados atuais</span>
                </div>

                <dl class="red-analysis__metrics">
                    <div>
                        <dt>Máx. vermelhos sem duplo</dt>
                        <dd>{{ formatarNumero(analise.maximoVermelhosSemDuplo) }}</dd>
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
                        <dt>Média de vermelhos sem duplo</dt>
                        <dd>{{ formatarNumero(analise.mediaVermelhosSemDuplo) }}</dd>
                    </div>
                    <div>
                        <dt>Último vermelho</dt>
                        <dd>{{ formatarHora(analise.ultimoVermelhoEm) }}</dd>
                    </div>
                </dl>
            </div>

            <footer class="card-footer red-analysis__footer">
                <span>{{ analise.totalRegistros }} registros analisados</span>
                <span>{{ analise.totalVermelhosDuplos }} vermelhos duplos</span>
            </footer>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { analisarVermelho } from '../analises/analisarVermelho';
import { useChillbetWheelFeed } from '../composables/useChillbetWheelFeed';

const props = defineProps({
    quantidade: {
        type: Number,
        default: 250,
    },
});

const { resultados } = useChillbetWheelFeed({ quantidade: props.quantidade });
const analise = computed(() => analisarVermelho(resultados.value, props.quantidade));
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
.red-analysis {
    min-height: 100%;
}

.red-analysis__header,
.red-analysis__footer,
.red-analysis__metrics > div {
    align-items: center;
    display: flex;
    justify-content: space-between;
}

.red-analysis__header {
    min-height: 76px;
}

.red-analysis__result-color {
    background-color: #dc3545;
    border: 2px solid rgba(220, 53, 69, 0.45);
    border-radius: 50%;
    box-shadow: 0 0.2rem 0.5rem rgba(0, 0, 0, 0.15);
    display: block;
    flex: 0 0 48px;
    height: 48px;
    width: 48px;
}

.red-analysis__current {
    border-bottom: 1px solid #dee2e6;
    display: flex;
    flex-direction: column;
    padding-bottom: 0.75rem;
}

.red-analysis__current strong {
    color: #dc3545;
    font-size: 2rem;
    line-height: 1;
}

.red-analysis__current span {
    color: #6c757d;
    margin-top: 0.3rem;
}

.red-analysis__metrics {
    margin: 0.5rem 0 0;
}

.red-analysis__metrics > div {
    border-bottom: 1px solid #edf0f2;
    gap: 1rem;
    padding: 0.5rem 0;
}

.red-analysis__metrics dt {
    color: #495057;
    font-size: 0.82rem;
    font-weight: 500;
}

.red-analysis__metrics dd {
    color: #dc3545;
    font-size: 0.9rem;
    font-weight: 700;
    margin: 0;
    text-align: right;
    white-space: nowrap;
}

.red-analysis__footer {
    color: #6c757d;
    font-size: 0.75rem;
}
</style>
