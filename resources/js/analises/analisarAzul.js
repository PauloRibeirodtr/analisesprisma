import { analisarSequenciasCor } from './analisarSequenciasCor.js';

export function analisarAzul(resultados, quantidade = 250) {
    const analise = analisarSequenciasCor(resultados, 'BLUE', quantidade);

    return {
        totalRegistros: analise.totalRegistros,
        totalAzuisIsolados: analise.totalIsolados,
        azuisIsoladosAtuais: analise.isoladosAtuais,
        totalAzuisDuplos: analise.totalDuplos,
        maximoAzuisSemDuplo: analise.maximoIsoladosSemDuplo,
        maximoMinutosSemDuplo: analise.maximoMinutosSemDuplo,
        mediaAzuisSemDuplo: analise.mediaIsoladosSemDuplo,
        mediaMinutosSemDuplo: analise.mediaMinutosSemDuplo,
        ultimoAzulEm: analise.ultimoResultadoEm,
    };
}
