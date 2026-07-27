import { computed, onMounted, onUnmounted, readonly, ref } from 'vue';
import { io } from 'socket.io-client';

const SOCKET_TOKEN_ENDPOINT = '/api/blaze/double/socket-token';
const RESULT_EVENT = 'double:resultado';
const TOKEN_EARLY_RENEWAL_MS = 60_000;
const TOKEN_MINIMUM_RENEWAL_MS = 30_000;

const ultimoResultado = ref(null);
const carregando = ref(false);
const socketConectado = ref(false);
const socketErro = ref('');
const ultimaAtualizacao = ref(null);
const ativo = ref(false);

let socket = null;
let tokenRenovacaoTimeout = null;
let conexaoPromise = null;
let renovandoToken = false;
let consumidoresAtivos = 0;

export function corEsperadaDouble(roll) {
    if (roll === 0) {
        return 0;
    }

    if (roll >= 1 && roll <= 7) {
        return 1;
    }

    if (roll >= 8 && roll <= 14) {
        return 2;
    }

    return null;
}

export function detalhesCorDouble(roll) {
    const color = corEsperadaDouble(roll);

    if (color === 0) {
        return {
            nome: 'Branco',
            faixa: 'Número 0',
            destaque: 'Pagamento 14x',
            classe: 'white',
        };
    }

    if (color === 1) {
        return {
            nome: 'Vermelho',
            faixa: 'Números 1 a 7',
            destaque: 'Aguardando o branco',
            classe: 'red',
        };
    }

    if (color === 2) {
        return {
            nome: 'Preto',
            faixa: 'Números 8 a 14',
            destaque: 'Aguardando o branco',
            classe: 'black',
        };
    }

    return null;
}

export function imagemResultadoDouble(roll) {
    return Number.isInteger(roll) && roll >= 0 && roll <= 14
        ? `/img/double/${roll}.png`
        : null;
}

export function normalizarResultadoDouble(resultado) {
    if (!resultado || typeof resultado !== 'object' || Array.isArray(resultado)) {
        return null;
    }

    const gameId = String(resultado.gameId || '').trim();
    const color = Number(resultado.color);
    const roll = Number(resultado.roll);
    const createdAt = String(resultado.createdAt || '').trim();
    const data = new Date(createdAt);

    if (
        !gameId
        || !Number.isInteger(color)
        || !Number.isInteger(roll)
        || color !== corEsperadaDouble(roll)
        || Number.isNaN(data.getTime())
    ) {
        return null;
    }

    return {
        gameId,
        color,
        roll,
        createdAt,
    };
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

async function obterTokenSocket() {
    const response = await fetch(SOCKET_TOKEN_ENDPOINT, {
        headers: {
            Accept: 'application/json',
        },
        credentials: 'same-origin',
    });
    const payload = await obterJson(response, 'Não foi possível autenticar o tempo real do Double.');

    if (!payload.token || !payload.ws_url || !payload.ws_path) {
        throw new Error('Os dados de conexão do tempo real do Double estão incompletos.');
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

function receberResultado(resultado) {
    const resultadoNormalizado = normalizarResultadoDouble(resultado);

    if (!resultadoNormalizado) {
        socketErro.value = 'O tempo real retornou um resultado inválido.';
        return;
    }

    ultimoResultado.value = resultadoNormalizado;
    ultimaAtualizacao.value = new Date();
    socketErro.value = '';
    carregando.value = false;
}

function iniciarSocket({ token, ws_url: wsUrl, ws_path: wsPath }) {
    desconectarSocket();

    socket = io(wsUrl, {
        path: wsPath,
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
        carregando.value = false;
    });

    socket.on(RESULT_EVENT, receberResultado);

    socket.on('connect_error', error => {
        socketConectado.value = false;
        carregando.value = false;
        socketErro.value = mensagemErro(error, 'Tempo real do Double indisponível.');

        if (/token|expirad|credencial|unauthor/i.test(socketErro.value)) {
            void renovarTokenSocket();
        }
    });

    socket.on('disconnect', () => {
        socketConectado.value = false;
    });
}

async function conectarSocket() {
    if (conexaoPromise) {
        return conexaoPromise;
    }

    carregando.value = ultimoResultado.value === null;
    socketErro.value = '';

    conexaoPromise = (async () => {
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

            carregando.value = false;
            socketErro.value = mensagemErro(error, 'Tempo real do Double indisponível.');
            agendarNovaTentativaToken();
            throw error;
        }
    })();

    try {
        await conexaoPromise;
    } finally {
        conexaoPromise = null;
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
    } catch (error) {
        if (ativo.value) {
            socketErro.value = mensagemErro(error, 'Não foi possível renovar o tempo real do Double.');
            agendarNovaTentativaToken();
        }
    } finally {
        renovandoToken = false;
    }
}

export async function iniciarBlazeDoubleFeed() {
    if (ativo.value) {
        return;
    }

    ativo.value = true;

    return conectarSocket();
}

export function pararBlazeDoubleFeed() {
    ativo.value = false;
    clearTimeout(tokenRenovacaoTimeout);
    tokenRenovacaoTimeout = null;
    desconectarSocket();
    carregando.value = false;
    renovandoToken = false;
}

export async function reconectarBlazeDoubleFeed() {
    desconectarSocket();
    ativo.value = true;

    return conectarSocket();
}

export function useBlazeDoubleFeed() {
    onMounted(() => {
        consumidoresAtivos += 1;
        void iniciarBlazeDoubleFeed().catch(() => {});
    });

    onUnmounted(() => {
        consumidoresAtivos = Math.max(consumidoresAtivos - 1, 0);

        if (consumidoresAtivos === 0) {
            pararBlazeDoubleFeed();
        }
    });

    return {
        ultimoResultado: readonly(ultimoResultado),
        detalhesResultado: computed(() => (
            ultimoResultado.value ? detalhesCorDouble(ultimoResultado.value.roll) : null
        )),
        carregando: readonly(carregando),
        socketConectado: readonly(socketConectado),
        socketErro: readonly(socketErro),
        ultimaAtualizacao: readonly(ultimaAtualizacao),
        reconectar: reconectarBlazeDoubleFeed,
    };
}
