import { analisarSequenciasCor } from './analisarSequenciasCor.js';

export function analisarVermelho(resultados, quantidade = 2000) {
    const analise = analisarSequenciasCor(resultados, 'RED', quantidade);

    return {
        totalRegistros: analise.totalRegistros,
        totalVermelhosIsolados: analise.totalIsolados,
        vermelhosIsoladosAtuais: analise.isoladosAtuais,
        totalVermelhosDuplos: analise.totalDuplos,
        maximoVermelhosSemDuplo: analise.maximoIsoladosSemDuplo,
        maximoMinutosSemDuplo: analise.maximoMinutosSemDuplo,
        mediaVermelhosSemDuplo: analise.mediaIsoladosSemDuplo,
        mediaMinutosSemDuplo: analise.mediaMinutosSemDuplo,
        ultimoVermelhoEm: analise.ultimoResultadoEm,
    };
}
