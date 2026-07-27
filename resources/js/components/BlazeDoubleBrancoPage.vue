<template>
    <div class="blaze-double-page">
        <div class="blaze-double-page__overview">
            <BlazeDoubleUltimoResultado />

            <aside class="blaze-double-guide" aria-labelledby="double-guide-title">
                <header>
                    <span class="prisma-panel__kicker">Referência rápida</span>
                    <h2 id="double-guide-title">Mapa do Double</h2>
                    <p>Identifique rapidamente a faixa do resultado recebido.</p>
                </header>

                <div class="blaze-double-guide__items">
                    <article class="blaze-double-guide__item blaze-double-guide__item--white">
                        <img :src="'/img/double/0.png'" alt="Número 0 branco">
                        <div>
                            <strong>Branco</strong>
                            <span>Número 0 · paga 14x</span>
                        </div>
                    </article>

                    <article class="blaze-double-guide__item blaze-double-guide__item--red">
                        <img :src="'/img/double/1.png'" alt="Exemplo de número vermelho">
                        <div>
                            <strong>Vermelho</strong>
                            <span>Números 1 a 7</span>
                        </div>
                    </article>

                    <article class="blaze-double-guide__item blaze-double-guide__item--black">
                        <img :src="'/img/double/14.png'" alt="Exemplo de número preto">
                        <div>
                            <strong>Preto</strong>
                            <span>Números 8 a 14</span>
                        </div>
                    </article>
                </div>

                <p class="blaze-double-guide__notice">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    O painel informa resultados; não representa promessa de ganho.
                </p>
            </aside>
        </div>

        <BlazeDoubleJanelasPagamento />
    </div>
</template>

<script setup>
import BlazeDoubleJanelasPagamento from './BlazeDoubleJanelasPagamento.vue';
import BlazeDoubleUltimoResultado from './BlazeDoubleUltimoResultado.vue';
</script>

<style scoped>
.blaze-double-page {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding-bottom: 0.65rem;
}

.blaze-double-page__overview {
    display: grid;
    grid-template-columns: minmax(0, 1.65fr) minmax(280px, 0.65fr);
    gap: 1rem;
}

.blaze-double-guide {
    padding: 1.35rem;
    border: 1px solid var(--prisma-border);
    border-radius: 18px;
    background:
        radial-gradient(circle at 100% 0, rgba(25, 206, 231, 0.11), transparent 36%),
        #fff;
    box-shadow: 0 12px 30px rgba(24, 38, 100, 0.07);
}

.blaze-double-guide header h2 {
    margin: 0.2rem 0 0.3rem;
    color: var(--prisma-navy);
    font-size: 1.28rem;
    font-weight: 800;
}

.blaze-double-guide header p {
    margin: 0;
    color: var(--prisma-muted);
    font-size: 0.85rem;
}

.blaze-double-guide__items {
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
    margin-top: 1.2rem;
}

.blaze-double-guide__item {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.8rem;
    border: 1px solid #e7ebf4;
    border-radius: 13px;
    background: #f8faff;
}

.blaze-double-guide__item img {
    width: 46px;
    height: 46px;
    filter: drop-shadow(0 5px 7px rgba(24, 38, 100, 0.16));
}

.blaze-double-guide__item strong,
.blaze-double-guide__item span {
    display: block;
}

.blaze-double-guide__item strong {
    color: var(--prisma-navy);
    font-size: 0.9rem;
}

.blaze-double-guide__item span {
    margin-top: 0.1rem;
    color: var(--prisma-muted);
    font-size: 0.73rem;
}

.blaze-double-guide__item--white {
    border-left: 4px solid #d7dce7;
}

.blaze-double-guide__item--red {
    border-left: 4px solid #ef2f50;
}

.blaze-double-guide__item--black {
    border-left: 4px solid #202633;
}

.blaze-double-guide__notice {
    display: flex;
    gap: 0.5rem;
    margin: 1.2rem 0 0;
    padding-top: 1rem;
    border-top: 1px solid #edf0f7;
    color: var(--prisma-muted);
    font-size: 0.74rem;
    line-height: 1.45;
}

.blaze-double-guide__notice i {
    margin-top: 0.1rem;
    color: var(--prisma-blue);
}

@media (max-width: 991.98px) {
    .blaze-double-page__overview {
        grid-template-columns: 1fr;
    }

    .blaze-double-guide__items {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .blaze-double-guide__items {
        grid-template-columns: 1fr;
    }
}
</style>
