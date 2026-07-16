import { createApp } from 'vue';
import ChillbetWheelPage from './components/ChillbetWheelPage.vue';

const elemento = document.getElementById('chillbet-wheel-app');

if (elemento) {
    const quantidadeHistorico = Number.parseInt(elemento.dataset.historyLimit, 10);
    const quantidadeAnalise = Number.parseInt(elemento.dataset.analysisLimit, 10);
    const app = createApp(ChillbetWheelPage, {
        quantidadeHistorico: Number.isFinite(quantidadeHistorico) ? quantidadeHistorico : 50,
        quantidadeAnalise: Number.isFinite(quantidadeAnalise) ? quantidadeAnalise : 250,
    });

    app.mount(elemento);
}
