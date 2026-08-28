const CORES = ['BLACK', 'BLUE', 'RED', 'GREEN'];
const TAMANHOS_PADRAO_PERMITIDOS = [3, 4, 5];
const MAXIMO_RESULTADOS_POS_PADRAO = 5;

function converterData(valor) {
    if (!valor) {
        return null;
    }

    const valorIso = String(valor).replace(' ', 'T');
    const possuiFuso = /(Z|[+-]\d{2}:?\d{2})$/i.test(valorIso);
    const data = new Date(possuiFuso ? valorIso : `${valorIso}-03:00`);

    return Number.isNaN(data.getTime()) ? null : data;
}

export function analisarPadraoAoVivo(resultados, quantidade = 2000, tamanhoPadrao = 4) {
    const quantidadeNormalizada = Math.max(Number.parseInt(quantidade, 10) || 1, 1);
    const tamanhoSolicitado = Number.parseInt(tamanhoPadrao, 10);
    const tamanhoNormalizado = TAMANHOS_PADRAO_PERMITIDOS.includes(tamanhoSolicitado)
        ? tamanhoSolicitado
        : 4;
    const registros = resultados
        .slice(0, quantidadeNormalizada)
        .map((resultado) => ({
            ...resultado,
            cor: String(resultado.cor || '').toUpperCase(),
            dataConvertida: converterData(resultado.data),
        }))
        .filter((resultado) => CORES.includes(resultado.cor) && resultado.dataConvertida)
        .sort((primeiro, segundo) => {
            const diferencaData = primeiro.dataConvertida - segundo.dataConvertida;

            return diferencaData || primeiro.gameId - segundo.gameId;
        });
    const padraoAtual = registros.slice(-tamanhoNormalizado).map((resultado) => resultado.cor);
    const contagemProximasCores = Object.fromEntries(CORES.map((cor) => [cor, 0]));
    const ocorrenciasPosPadrao = [];

    if (padraoAtual.length === tamanhoNormalizado) {
        for (let inicio = 0; inicio + tamanhoNormalizado < registros.length; inicio += 1) {
            const corresponde = padraoAtual.every((cor, deslocamento) => (
                registros[inicio + deslocamento].cor === cor
            ));

            if (corresponde) {
                const resultadoPosPadrao = registros[inicio + tamanhoNormalizado];

                contagemProximasCores[resultadoPosPadrao.cor] += 1;
                ocorrenciasPosPadrao.push(resultadoPosPadrao);
            }
        }
    }

    const totalOcorrencias = Object.values(contagemProximasCores)
        .reduce((total, ocorrencias) => total + ocorrencias, 0);
    const distribuicao = CORES.map((cor) => ({
        cor,
        total: contagemProximasCores[cor],
        percentual: totalOcorrencias > 0
            ? (contagemProximasCores[cor] / totalOcorrencias) * 100
            : 0,
    }));
    const maiorTotal = Math.max(...distribuicao.map((item) => item.total));
    const coresMaisFrequentes = maiorTotal > 0
        ? distribuicao.filter((item) => item.total === maiorTotal)
        : [];

    return {
        totalRegistros: registros.length,
        tamanhoPadrao: tamanhoNormalizado,
        padraoAtual,
        padraoCompleto: padraoAtual.length === tamanhoNormalizado,
        totalOcorrencias,
        distribuicao,
        coresMaisFrequentes,
        resultadosPosPadrao: ocorrenciasPosPadrao.slice(-MAXIMO_RESULTADOS_POS_PADRAO),
    };
}
