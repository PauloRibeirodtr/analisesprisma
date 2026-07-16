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

export function analisarVerde(resultados, quantidade = 250) {
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

    const verdes = registros
        .map((resultado, indice) => ({ ...resultado, indice }))
        .filter((resultado) => resultado.cor === 'GREEN');
    const intervalos = verdes.slice(1).map((verde, indice) => {
        const verdeAnterior = verdes[indice];

        return {
            distanciaRodadas: verde.indice - verdeAnterior.indice - 1,
            distanciaMinutos: (verde.dataConvertida - verdeAnterior.dataConvertida) / 60_000,
        };
    });
    const distanciasRodadas = intervalos.map((intervalo) => intervalo.distanciaRodadas);
    const distanciasMinutos = intervalos.map((intervalo) => intervalo.distanciaMinutos);

    return {
        totalRegistros: registros.length,
        totalVerdes: verdes.length,
        totalIntervalos: intervalos.length,
        maximaDistanciaRodadas: maximo(distanciasRodadas),
        mediaDistanciaRodadas: media(distanciasRodadas),
        maximaDistanciaMinutos: maximo(distanciasMinutos),
        mediaDistanciaMinutos: media(distanciasMinutos),
        ultimoVerdeEm: verdes.at(-1)?.dataConvertida ?? null,
    };
}
