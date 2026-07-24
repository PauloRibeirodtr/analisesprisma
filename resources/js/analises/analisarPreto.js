import { analisarSequenciasCor } from './analisarSequenciasCor.js';

export function analisarPreto(resultados, quantidade = 2000) {
    const analise = analisarSequenciasCor(resultados, 'BLACK', quantidade);

    return {
        totalRegistros: analise.totalRegistros,
        totalPretosIsolados: analise.totalIsolados,
        pretosIsoladosAtuais: analise.isoladosAtuais,
        totalPretosDuplos: analise.totalDuplos,
        maximoPretosSemDuplo: analise.maximoIsoladosSemDuplo,
        maximoMinutosSemDuplo: analise.maximoMinutosSemDuplo,
        mediaPretosSemDuplo: analise.mediaIsoladosSemDuplo,
        mediaMinutosSemDuplo: analise.mediaMinutosSemDuplo,
        ultimoPretoEm: analise.ultimoResultadoEm,
    };
}
