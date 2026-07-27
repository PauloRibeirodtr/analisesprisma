<template>
    <section class="double-result-card" aria-labelledby="double-result-title">
        <header class="double-result-card__header">
            <div>
                <span class="prisma-panel__kicker">Monitor em tempo real</span>
                <h2 id="double-result-title">Último resultado do Double</h2>
            </div>

            <span
                class="badge double-result-card__status"
                :class="socketConectado ? 'badge-success' : 'badge-secondary'"
            >
                <span class="double-result-card__status-dot" aria-hidden="true"></span>
                {{ socketConectado ? 'Conectado' : 'Desconectado' }}
            </span>
        </header>

        <div v-if="carregando && !ultimoResultado" class="double-result-card__feedback">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Carregando...</span>
            </div>
            <strong>Conectando ao Double...</strong>
            <span>Aguardando o último resultado disponível.</span>
        </div>

        <div v-else-if="socketErro && !ultimoResultado" class="double-result-card__feedback">
            <div class="alert alert-warning mb-0" role="alert">
                <h3 class="alert-heading h6">Tempo real indisponível</h3>
                <p class="mb-3">{{ socketErro }}</p>
                <button type="button" class="btn btn-sm btn-outline-warning" @click="tentarNovamente">
                    <i class="fas fa-sync-alt mr-1" aria-hidden="true"></i>
                    Tentar novamente
                </button>
            </div>
        </div>

        <div v-else-if="!ultimoResultado" class="double-result-card__feedback">
            <i class="far fa-clock double-result-card__empty-icon" aria-hidden="true"></i>
            <strong>Aguardando o primeiro resultado</strong>
            <span>A conexão está pronta para receber o próximo giro.</span>
        </div>

        <div v-else class="double-result-card__body">
            <div
                class="double-result-card__outcome"
                :class="`double-result-card__outcome--${detalhesResultado.classe}`"
            >
                <span class="double-result-card__live">
                    <span aria-hidden="true"></span>
                    Último giro
                </span>

                <img
                    :src="imagemResultadoDouble(ultimoResultado.roll)"
                    :alt="`Resultado ${ultimoResultado.roll}, ${detalhesResultado.nome}`"
                    class="double-result-card__image"
                >

                <div class="double-result-card__identity">
                    <span>Resultado</span>
                    <strong>{{ detalhesResultado.nome }}</strong>
                    <small>{{ detalhesResultado.destaque }}</small>
                </div>
            </div>

            <dl class="double-result-card__details">
                <div>
                    <dt>Número</dt>
                    <dd>{{ ultimoResultado.roll }}</dd>
                </div>
                <div>
                    <dt>Faixa</dt>
                    <dd>{{ detalhesResultado.faixa }}</dd>
                </div>
                <div>
                    <dt>Horário</dt>
                    <dd>{{ formatarHorario(ultimoResultado.createdAt) }}</dd>
                </div>
                <div>
                    <dt>Jogo</dt>
                    <dd :title="ultimoResultado.gameId">{{ abreviarGameId(ultimoResultado.gameId) }}</dd>
                </div>
            </dl>

            <div v-if="socketErro" class="alert alert-warning double-result-card__warning" role="alert">
                <i class="fas fa-exclamation-triangle mr-1" aria-hidden="true"></i>
                Exibindo o último resultado recebido. {{ socketErro }}
            </div>

            <p v-if="ultimaAtualizacao" class="double-result-card__updated">
                Atualizado no painel às {{ formatarHorarioLocal(ultimaAtualizacao) }}
            </p>
        </div>
    </section>
</template>

<script setup>
import {
    imagemResultadoDouble,
    useBlazeDoubleFeed,
} from '../composables/useBlazeDoubleFeed';

const {
    ultimoResultado,
    detalhesResultado,
    carregando,
    socketConectado,
    socketErro,
    ultimaAtualizacao,
    reconectar,
} = useBlazeDoubleFeed();

function converterData(valor) {
    const data = valor instanceof Date ? valor : new Date(valor);

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

function formatarHorarioLocal(valor) {
    const data = converterData(valor);

    return data
        ? data.toLocaleTimeString('pt-BR', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        })
        : '--:--:--';
}

function abreviarGameId(gameId) {
    return gameId.length > 18
        ? `${gameId.slice(0, 8)}…${gameId.slice(-6)}`
        : gameId;
}

function tentarNovamente() {
    void reconectar().catch(() => {});
}
</script>

<style scoped>
.double-result-card {
    overflow: hidden;
    border: 1px solid var(--prisma-border);
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 12px 30px rgba(24, 38, 100, 0.07);
}

.double-result-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.4rem;
    border-bottom: 1px solid #edf0f7;
}

.double-result-card__header h2 {
    margin: 0.2rem 0 0;
    color: var(--prisma-navy);
    font-size: 1.28rem;
    font-weight: 800;
}

.double-result-card__status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.65rem;
}

.double-result-card__status-dot,
.double-result-card__live span {
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 50%;
    background: currentColor;
}

.double-result-card__feedback {
    display: flex;
    min-height: 360px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 2rem;
    color: var(--prisma-muted);
    text-align: center;
}

.double-result-card__feedback strong {
    color: var(--prisma-navy);
    font-size: 1.05rem;
}

.double-result-card__feedback .alert {
    width: min(100%, 520px);
}

.double-result-card__empty-icon {
    color: var(--prisma-blue);
    font-size: 2rem;
}

.double-result-card__body {
    padding: 1.4rem;
}

.double-result-card__outcome {
    position: relative;
    display: grid;
    min-height: 230px;
    grid-template-columns: minmax(130px, 0.75fr) minmax(180px, 1.25fr);
    align-items: center;
    gap: 1.5rem;
    overflow: hidden;
    padding: 2rem;
    border-radius: 16px;
    color: #fff;
}

.double-result-card__outcome::after {
    position: absolute;
    right: -70px;
    bottom: -110px;
    width: 260px;
    height: 260px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 50%;
    content: "";
}

.double-result-card__outcome--white {
    border: 1px solid #e1e6ef;
    background:
        radial-gradient(circle at 82% 15%, rgba(255, 255, 255, 0.95), transparent 30%),
        linear-gradient(135deg, #f8f9fc, #e8ecf4);
    color: var(--prisma-navy);
}

.double-result-card__outcome--red {
    background:
        radial-gradient(circle at 85% 15%, rgba(255, 255, 255, 0.18), transparent 28%),
        linear-gradient(135deg, #b81332, #ef2f50);
}

.double-result-card__outcome--black {
    background:
        radial-gradient(circle at 85% 15%, rgba(255, 255, 255, 0.14), transparent 28%),
        linear-gradient(135deg, #10141f, #2d3443);
}

.double-result-card__live {
    position: absolute;
    top: 1rem;
    left: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.double-result-card__image {
    position: relative;
    z-index: 1;
    width: 132px;
    height: 132px;
    justify-self: center;
    filter: drop-shadow(0 14px 18px rgba(4, 9, 30, 0.24));
}

.double-result-card__identity {
    position: relative;
    z-index: 1;
}

.double-result-card__identity span,
.double-result-card__identity strong,
.double-result-card__identity small {
    display: block;
}

.double-result-card__identity span {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    opacity: 0.72;
    text-transform: uppercase;
}

.double-result-card__identity strong {
    margin: 0.2rem 0;
    font-size: clamp(2rem, 6vw, 3.6rem);
    line-height: 1;
}

.double-result-card__identity small {
    font-size: 0.95rem;
    font-weight: 700;
    opacity: 0.85;
}

.double-result-card__details {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
    margin: 1rem 0 0;
}

.double-result-card__details div {
    min-width: 0;
    padding: 0.9rem;
    border: 1px solid #e7ebf4;
    border-radius: 12px;
    background: #f8faff;
}

.double-result-card__details dt {
    color: var(--prisma-muted);
    font-size: 0.66rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.double-result-card__details dd {
    overflow: hidden;
    margin: 0.3rem 0 0;
    color: var(--prisma-navy);
    font-weight: 800;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.double-result-card__warning {
    margin: 1rem 0 0;
}

.double-result-card__updated {
    margin: 0.8rem 0 0;
    color: var(--prisma-muted);
    font-size: 0.75rem;
    text-align: right;
}

@media (max-width: 767.98px) {
    .double-result-card__header {
        align-items: flex-start;
        flex-direction: column;
    }

    .double-result-card__outcome {
        grid-template-columns: 1fr;
        gap: 0.75rem;
        padding: 3rem 1.25rem 1.5rem;
        text-align: center;
    }

    .double-result-card__image {
        width: 112px;
        height: 112px;
    }

    .double-result-card__details {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
