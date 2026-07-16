function converterData(valor) {
    if (!valor) {
        return null;
    }

    const valorIso = String(valor).replace(' ', 'T');
    const possuiFuso = /(Z|[+-]\d{2}:?\d{2})$/i.test(valorIso);
    const data = new Date(possuiFuso ? valorIso : `${valorIso}-03:00`);

    return Number.isNaN(data.getTime()) ? null : data;
}

function media(valores) {
    if (valores.length === 0) {
        return null;
    }

    return valores.reduce((total, valor) => total + valor, 0) / valores.length;
}

function maximo(valores) {
    return valores.length === 0 ? null : Math.max(...valores);
}

export function analisarSequenciasCor(resultados, cor, quantidade = 250) {
    const quantidadeNormalizada = Math.max(Number.parseInt(quantidade, 10) || 1, 1);
    const registros = resultados
        .slice(0, quantidadeNormalizada)
        .map((resultado) => ({
            ...resultado,
            dataConvertida: converterData(resultado.data),
        }))
        .filter((resultado) => resultado.dataConvertida)
        .sort((primeiro, segundo) => {
            const diferencaData = primeiro.dataConvertida - segundo.dataConvertida;

            return diferencaData || primeiro.gameId - segundo.gameId;
        });

    const isolados = [];
    const duplos = [];
    let indice = 0;

    while (indice < registros.length) {
        if (registros[indice].cor !== cor) {
            indice += 1;

            continue;
        }

        const inicioSequencia = indice;

        while (indice + 1 < registros.length && registros[indice + 1].cor === cor) {
            indice += 1;
        }

        const fimSequencia = indice;
        const tamanhoSequencia = fimSequencia - inicioSequencia + 1;

        if (tamanhoSequencia >= 2) {
            duplos.push(registros[inicioSequencia + 1]);
        } else if (inicioSequencia > 0 && fimSequencia < registros.length - 1) {
            isolados.push(registros[inicioSequencia]);
        }

        indice += 1;
    }

    const intervalos = duplos.slice(1).map((duplo, posicao) => {
        const duploAnterior = duplos[posicao];
        const isoladosSemDuplo = isolados.filter((isolado) => (
            isolado.dataConvertida > duploAnterior.dataConvertida
            && isolado.dataConvertida < duplo.dataConvertida
        )).length;

        return {
            isoladosSemDuplo,
            minutosSemDuplo: (duplo.dataConvertida - duploAnterior.dataConvertida) / 60_000,
        };
    });

    const ultimoDuplo = duplos.at(-1);
    const isoladosAtuais = isolados.filter((isolado) => (
        !ultimoDuplo || isolado.dataConvertida > ultimoDuplo.dataConvertida
    )).length;
    const isoladosSemDuplo = intervalos.map((intervalo) => intervalo.isoladosSemDuplo);
    const minutosSemDuplo = intervalos.map((intervalo) => intervalo.minutosSemDuplo);
    const ultimoRegistro = registros.at(-1);
    const minutosCicloAtual = ultimoDuplo && ultimoRegistro
        ? (ultimoRegistro.dataConvertida - ultimoDuplo.dataConvertida) / 60_000
        : null;
    const isoladosParaMaximo = registros.length > 0
        ? [...isoladosSemDuplo, isoladosAtuais]
        : isoladosSemDuplo;
    const minutosParaMaximo = minutosCicloAtual === null
        ? minutosSemDuplo
        : [...minutosSemDuplo, minutosCicloAtual];
    const ultimoResultado = [...registros].reverse().find((resultado) => resultado.cor === cor);

    return {
        totalRegistros: registros.length,
        totalIsolados: isolados.length,
        isoladosAtuais,
        totalDuplos: duplos.length,
        maximoIsoladosSemDuplo: maximo(isoladosParaMaximo),
        maximoMinutosSemDuplo: maximo(minutosParaMaximo),
        mediaIsoladosSemDuplo: media(isoladosSemDuplo),
        mediaMinutosSemDuplo: media(minutosSemDuplo),
        ultimoResultadoEm: ultimoResultado?.dataConvertida ?? null,
    };
}
