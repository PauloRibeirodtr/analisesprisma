import { computed, onMounted, onUnmounted, readonly, ref } from 'vue';
import { io } from 'socket.io-client';

export const CHILLBET_DEFAULT_HISTORY_LIMIT = 300;
export const CHILLBET_MAX_HISTORY_LIMIT = 1000;

const HISTORY_ENDPOINT = '/api/chillbet/wheel/ultimos';
const SOCKET_TOKEN_ENDPOINT = '/api/chillbet/wheel/socket-token';
const RESULT_EVENT = 'wheel:resultado';
const VALID_COLORS = new Set(['BLACK', 'RED', 'BLUE', 'GREEN']);
const TOKEN_EARLY_RENEWAL_MS = 60_000;
const TOKEN_MINIMUM_RENEWAL_MS = 30_000;

const resultados = ref([]);
const limite = ref(CHILLBET_DEFAULT_HISTORY_LIMIT);
const carregando = ref(false);
const sincronizando = ref(false);
const erro = ref('');
const socketConectado = ref(false);
const socketErro = ref('');
const ultimaAtualizacao = ref(null);
const ativo = ref(false);

let socket = null;
let tokenRenovacaoTimeout = null;
let historicoAbortController = null;
let sincronizacaoPromise = null;
let inicializacaoPromise = null;
let renovandoToken = false;
let consumidoresAtivos = 0;

function normalizarLimite(valor) {
    const limiteNumerico = Number.parseInt(valor, 10);

    if (!Number.isFinite(limiteNumerico)) {
        return CHILLBET_DEFAULT_HISTORY_LIMIT;
    }

    return Math.min(Math.max(limiteNumerico, 1), CHILLBET_MAX_HISTORY_LIMIT);
}

export function normalizarResultadoChillbet(resultado) {
    if (!resultado || typeof resultado !== 'object') {
        return null;
    }

    const gameId = Number(resultado.gameId);
    const cor = String(resultado.cor || '').toUpperCase();
    const data = String(resultado.data || '');

    if (!Number.isInteger(gameId) || gameId <= 0 || !VALID_COLORS.has(cor) || !data) {
        return null;
    }

    return {
        id: Number(resultado.id) || gameId,
        gameId,
        cor,
        data,
    };
}

export function mesclarResultadosChillbet(resultadosAtuais, novosResultados, quantidadeMaxima) {
    const resultadosPorJogo = new Map();

    [...resultadosAtuais, ...novosResultados].forEach((resultado) => {
        const resultadoNormalizado = normalizarResultadoChillbet(resultado);

        if (resultadoNormalizado) {
            resultadosPorJogo.set(resultadoNormalizado.gameId, resultadoNormalizado);
        }
    });

    return [...resultadosPorJogo.values()]
        .sort((primeiro, segundo) => {
            const comparacaoData = segundo.data.localeCompare(primeiro.data);

            return comparacaoData || segundo.gameId - primeiro.gameId;
        })
        .slice(0, normalizarLimite(quantidadeMaxima));
}

async function obterJson(response, mensagemPadrao) {
    let payload;

    try {
        payload = await response.json();
    } catch {
        throw new Error(mensagemPadrao);
    }

    if (!response.ok) {
        throw new Error(payload?.message || mensagemPadrao);
    }

    return payload;
}

function mensagemErro(error, mensagemPadrao) {
    return error instanceof Error && error.message ? error.message : mensagemPadrao;
}

export async function sincronizarHistoricoChillbet({ mostrarCarregamento = resultados.value.length === 0 } = {}) {
    if (sincronizacaoPromise) {
        return sincronizacaoPromise;
    }

    sincronizacaoPromise = (async () => {
        if (mostrarCarregamento) {
            carregando.value = true;
        }

        sincronizando.value = true;
        historicoAbortController = new AbortController();

        try {
            const parametros = new URLSearchParams({ limit: String(limite.value) });
            const response = await fetch(`${HISTORY_ENDPOINT}?${parametros.toString()}`, {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                signal: historicoAbortController.signal,
            });
            const payload = await obterJson(response, 'Não foi possível carregar o histórico ChillBet.');

            if (!Array.isArray(payload.resultados)) {
                throw new Error('O histórico ChillBet retornou um formato inválido.');
            }

            if (!ativo.value) {
                return;
            }

            resultados.value = mesclarResultadosChillbet(
                resultados.value,
                payload.resultados,
                limite.value,
            );
            erro.value = '';
            ultimaAtualizacao.value = new Date();
        } catch (error) {
            if (error?.name === 'AbortError' || !ativo.value) {
                return;
            }

            erro.value = mensagemErro(error, 'Não foi possível carregar o histórico ChillBet.');
            throw error;
        } finally {
            historicoAbortController = null;
            carregando.value = false;
            sincronizando.value = false;
        }
    })();

    try {
        await sincronizacaoPromise;
    } finally {
        sincronizacaoPromise = null;
    }
}

async function obterTokenSocket() {
    const response = await fetch(SOCKET_TOKEN_ENDPOINT, {
        headers: {
            Accept: 'application/json',
        },
        credentials: 'same-origin',
    });
    const payload = await obterJson(response, 'Não foi possível autenticar o tempo real ChillBet.');

    if (!payload.token || !payload.ws_url) {
        throw new Error('Os dados de conexão do tempo real ChillBet estão incompletos.');
    }

    return payload;
}

function desconectarSocket() {
    if (!socket) {
        return;
    }

    socket.removeAllListeners();
    socket.disconnect();
    socket = null;
    socketConectado.value = false;
}

function agendarRenovacaoToken({ expires_at: expiresAt }) {
    clearTimeout(tokenRenovacaoTimeout);

    if (!expiresAt || !ativo.value) {
        return;
    }

    const renovacaoEm = Math.max(
        Number(expiresAt) * 1000 - Date.now() - TOKEN_EARLY_RENEWAL_MS,
        TOKEN_MINIMUM_RENEWAL_MS,
    );

    tokenRenovacaoTimeout = setTimeout(() => {
        void renovarTokenSocket();
    }, renovacaoEm);
}

function agendarNovaTentativaToken() {
    clearTimeout(tokenRenovacaoTimeout);

    if (!ativo.value) {
        return;
    }

    tokenRenovacaoTimeout = setTimeout(() => {
        void renovarTokenSocket();
    }, TOKEN_MINIMUM_RENEWAL_MS);
}

function adicionarResultado(resultado) {
    const resultadoNormalizado = normalizarResultadoChillbet(resultado);

    if (!resultadoNormalizado) {
        return;
    }

    resultados.value = mesclarResultadosChillbet(
        resultados.value,
        [resultadoNormalizado],
        limite.value,
    );
    ultimaAtualizacao.value = new Date();
}

function iniciarSocket({ token, ws_url: wsUrl }) {
    desconectarSocket();

    socket = io(wsUrl, {
        transports: ['websocket'],
        auth: { token },
        reconnection: true,
        reconnectionAttempts: Infinity,
        reconnectionDelay: 2_000,
        reconnectionDelayMax: 30_000,
        timeout: 10_000,
    });

    socket.on('connect', () => {
        socketConectado.value = true;
        socketErro.value = '';

        void sincronizarHistoricoChillbet({ mostrarCarregamento: false }).catch(() => {});
    });

    socket.on(RESULT_EVENT, adicionarResultado);

    socket.on('connect_error', (error) => {
        socketConectado.value = false;
        socketErro.value = mensagemErro(error, 'Tempo real ChillBet indisponível.');

        if (/token|expirad|credencial|unauthor/i.test(socketErro.value)) {
            void renovarTokenSocket();
        }
    });

    socket.on('disconnect', () => {
        socketConectado.value = false;
    });
}

async function conectarSocket() {
    try {
        const tokenData = await obterTokenSocket();

        if (!ativo.value) {
            return;
        }

        iniciarSocket(tokenData);
        agendarRenovacaoToken(tokenData);
    } catch (error) {
        if (!ativo.value) {
            return;
        }

        socketErro.value = mensagemErro(error, 'Tempo real ChillBet indisponível.');
        agendarNovaTentativaToken();
        throw error;
    }
}

async function renovarTokenSocket() {
    if (!ativo.value || renovandoToken) {
        return;
    }

    renovandoToken = true;

    try {
        const tokenData = await obterTokenSocket();

        if (!ativo.value) {
            return;
        }

        iniciarSocket(tokenData);
        agendarRenovacaoToken(tokenData);
        await sincronizarHistoricoChillbet({ mostrarCarregamento: false });
    } catch (error) {
        if (ativo.value) {
            socketErro.value = mensagemErro(error, 'Não foi possível renovar o tempo real ChillBet.');
            agendarNovaTentativaToken();
        }
    } finally {
        renovandoToken = false;
    }
}

export async function iniciarChillbetWheelFeed(quantidade = CHILLBET_DEFAULT_HISTORY_LIMIT) {
    const novoLimite = normalizarLimite(quantidade);

    if (ativo.value) {
        const limiteAnterior = limite.value;
        limite.value = Math.max(limite.value, novoLimite);

        if (limite.value > limiteAnterior) {
            await sincronizarHistoricoChillbet({ mostrarCarregamento: false });
        }

        return;
    }

    if (inicializacaoPromise) {
        await inicializacaoPromise;

        return iniciarChillbetWheelFeed(novoLimite);
    }

    limite.value = novoLimite;
    ativo.value = true;
    erro.value = '';
    socketErro.value = '';

    inicializacaoPromise = Promise.allSettled([
        sincronizarHistoricoChillbet(),
        conectarSocket(),
    ]).finally(() => {
        inicializacaoPromise = null;
    });

    return inicializacaoPromise;
}

export function pararChillbetWheelFeed() {
    ativo.value = false;
    clearTimeout(tokenRenovacaoTimeout);
    tokenRenovacaoTimeout = null;
    historicoAbortController?.abort();
    desconectarSocket();
    carregando.value = false;
    sincronizando.value = false;
    renovandoToken = false;
}

export function useChillbetWheelFeed({
    quantidade = CHILLBET_DEFAULT_HISTORY_LIMIT,
    iniciarAutomaticamente = true,
} = {}) {
    onMounted(() => {
        if (!iniciarAutomaticamente) {
            return;
        }

        consumidoresAtivos += 1;
        void iniciarChillbetWheelFeed(quantidade);
    });

    onUnmounted(() => {
        if (!iniciarAutomaticamente) {
            return;
        }

        consumidoresAtivos = Math.max(consumidoresAtivos - 1, 0);

        if (consumidoresAtivos === 0) {
            pararChillbetWheelFeed();
        }
    });

    return {
        resultados: readonly(resultados),
        limite: readonly(limite),
        totalResultados: computed(() => resultados.value.length),
        carregando: readonly(carregando),
        sincronizando: readonly(sincronizando),
        erro: readonly(erro),
        socketConectado: readonly(socketConectado),
        socketErro: readonly(socketErro),
        ultimaAtualizacao: readonly(ultimaAtualizacao),
        ativo: readonly(ativo),
        iniciar: iniciarChillbetWheelFeed,
        parar: pararChillbetWheelFeed,
        sincronizar: sincronizarHistoricoChillbet,
    };
}
