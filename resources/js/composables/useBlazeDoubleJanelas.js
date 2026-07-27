import { onMounted, onUnmounted, readonly, ref } from 'vue';

const ENDPOINT = '/api/blaze/double/janelas-pagamento';
const INTERVALO_ATUALIZACAO_MS = 10_000;
const STATUS_VALIDOS = new Set(['agendada', 'executando', 'win', 'loss']);

const painel = ref(null);
const carregando = ref(false);
const atualizando = ref(false);
const erro = ref('');

let intervalo = null;
let requisicaoAtual = null;
let consumidoresAtivos = 0;

function dataValida(valor) {
    const data = new Date(valor);

    return Number.isNaN(data.getTime()) ? null : String(valor);
}

export function normalizarJanelaPagamento(janela) {
    if (!janela || typeof janela !== 'object' || Array.isArray(janela)) {
        return null;
    }

    const id = Number(janela.id);
    const inicioEm = dataValida(janela.inicioEm);
    const fimEm = dataValida(janela.fimEm);
    const horarioAlvoEm = dataValida(janela.horarioAlvoEm);
    const status = String(janela.status || '');

    if (
        !Number.isInteger(id)
        || id < 1
        || !inicioEm
        || !fimEm
        || !horarioAlvoEm
        || !STATUS_VALIDOS.has(status)
    ) {
        return null;
    }

    return {
        id,
        origemId: String(janela.origemId || ''),
        inicioEm,
        fimEm,
        horarioAlvoEm,
        status,
        ordemNoCiclo: Number.isInteger(Number(janela.ordemNoCiclo))
            ? Number(janela.ordemNoCiclo)
            : null,
        tirosNaJanela: Math.max(Number(janela.tirosNaJanela) || 0, 0),
        tirosAcumuladosCiclo: Math.max(
            Number(janela.tirosAcumuladosCiclo) || 0,
            0,
        ),
        tirosNoCicloAtual: janela.tirosNoCicloAtual === null
            || janela.tirosNoCicloAtual === undefined
            ? null
            : Math.max(Number(janela.tirosNoCicloAtual) || 0, 0),
        fonteIndisponivel: Boolean(janela.fonteIndisponivel),
        posicaoAnterior: Number.isInteger(Number(janela.posicaoAnterior))
            ? Number(janela.posicaoAnterior)
            : null,
    };
}

export function normalizarPainelJanelas(payload) {
    if (
        !payload
        || typeof payload !== 'object'
        || Array.isArray(payload)
        || !Array.isArray(payload.proximas)
        || !Array.isArray(payload.anteriores)
    ) {
        return null;
    }

    const proximas = payload.proximas
        .map(normalizarJanelaPagamento)
        .filter(Boolean);
    const anteriores = payload.anteriores
        .map(normalizarJanelaPagamento)
        .filter(Boolean);

    if (
        proximas.length !== payload.proximas.length
        || anteriores.length !== payload.anteriores.length
    ) {
        return null;
    }

    return {
        timezone: String(payload.timezone || 'UTC'),
        referenciaEm: dataValida(payload.referenciaEm),
        atualizadoEm: payload.atualizadoEm
            ? dataValida(payload.atualizadoEm)
            : null,
        proximas,
        anteriores,
        cicloAtual: payload.cicloAtual ? {
            id: Number(payload.cicloAtual.id),
            ordemJanela: Math.max(
                Number(payload.cicloAtual.ordemJanela) || 0,
                0,
            ),
            totalTiros: Math.max(
                Number(payload.cicloAtual.totalTiros) || 0,
                0,
            ),
            sequenciaLosses: Math.max(
                Number(payload.cicloAtual.sequenciaLosses) || 0,
                0,
            ),
        } : null,
        ultimoWin: payload.ultimoWin ? {
            acertoEm: dataValida(payload.ultimoWin.acertoEm),
            totalTiros: Math.max(
                Number(payload.ultimoWin.totalTiros) || 0,
                0,
            ),
        } : null,
    };
}

async function obterJson(response) {
    let payload;

    try {
        payload = await response.json();
    } catch {
        throw new Error('O painel de janelas retornou uma resposta inválida.');
    }

    if (!response.ok) {
        throw new Error(
            payload?.message
            || payload?.erro
            || 'Não foi possível carregar as janelas de pagamento.',
        );
    }

    return payload;
}

export async function atualizarJanelasPagamento() {
    if (requisicaoAtual) {
        return requisicaoAtual;
    }

    atualizando.value = painel.value !== null;
    carregando.value = painel.value === null;

    requisicaoAtual = (async () => {
        try {
            const response = await fetch(ENDPOINT, {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });
            const payload = normalizarPainelJanelas(
                await obterJson(response),
            );

            if (!payload) {
                throw new Error(
                    'O painel de janelas retornou dados inválidos.',
                );
            }

            painel.value = payload;
            erro.value = '';
        } catch (error) {
            erro.value = error instanceof Error
                ? error.message
                : 'Não foi possível carregar as janelas de pagamento.';
            throw error;
        } finally {
            carregando.value = false;
            atualizando.value = false;
        }
    })();

    try {
        await requisicaoAtual;
    } finally {
        requisicaoAtual = null;
    }
}

function iniciarAtualizacao() {
    if (intervalo) {
        return;
    }

    void atualizarJanelasPagamento().catch(() => {});
    intervalo = setInterval(() => {
        void atualizarJanelasPagamento().catch(() => {});
    }, INTERVALO_ATUALIZACAO_MS);
}

function pararAtualizacao() {
    clearInterval(intervalo);
    intervalo = null;
}

export function useBlazeDoubleJanelas() {
    onMounted(() => {
        consumidoresAtivos += 1;
        iniciarAtualizacao();
    });

    onUnmounted(() => {
        consumidoresAtivos = Math.max(consumidoresAtivos - 1, 0);

        if (consumidoresAtivos === 0) {
            pararAtualizacao();
        }
    });

    return {
        painel: readonly(painel),
        carregando: readonly(carregando),
        atualizando: readonly(atualizando),
        erro: readonly(erro),
        atualizar: atualizarJanelasPagamento,
    };
}
