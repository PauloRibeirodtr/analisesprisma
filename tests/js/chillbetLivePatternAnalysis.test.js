import assert from 'node:assert/strict';
import test from 'node:test';
import { analisarPadraoAoVivo } from '../../resources/js/analises/analisarPadraoAoVivo.js';

function criarResultadosCronologicos(cores) {
    return cores.map((cor, indice) => ({
        gameId: indice + 1,
        cor,
        data: `2026-07-15 10:${String(indice).padStart(2, '0')}:00`,
    })).reverse();
}

test('encontra o padrão atual e distribui as próximas cores históricas', () => {
    const padrao = ['BLUE', 'RED', 'GREEN', 'RED'];
    const resultados = criarResultadosCronologicos([
        ...padrao,
        'BLACK',
        'GREEN',
        ...padrao,
        'BLUE',
        'BLACK',
        ...padrao,
    ]);
    const analise = analisarPadraoAoVivo(resultados, 250);
    const distribuicao = Object.fromEntries(
        analise.distribuicao.map((item) => [item.cor, item]),
    );

    assert.deepEqual(analise.padraoAtual, padrao);
    assert.equal(analise.totalOcorrencias, 2);
    assert.equal(distribuicao.BLACK.total, 1);
    assert.equal(distribuicao.BLACK.percentual, 50);
    assert.equal(distribuicao.BLUE.total, 1);
    assert.equal(distribuicao.BLUE.percentual, 50);
    assert.equal(distribuicao.RED.total, 0);
    assert.equal(distribuicao.GREEN.total, 0);
    assert.equal(analise.coresMaisFrequentes.length, 2);
    assert.deepEqual(
        analise.resultadosPosPadrao.map((resultado) => resultado.cor),
        ['BLACK', 'BLUE'],
    );
});

test('não conta o padrão atual como ocorrência sem próxima pedra', () => {
    const padrao = ['BLACK', 'GREEN', 'BLACK', 'RED'];
    const analise = analisarPadraoAoVivo(criarResultadosCronologicos(padrao), 250);

    assert.equal(analise.padraoCompleto, true);
    assert.equal(analise.totalOcorrencias, 0);
    assert.equal(analise.distribuicao.every((item) => item.percentual === 0), true);
});

test('respeita a quantidade de registros da base selecionada', () => {
    const padrao = ['BLACK', 'GREEN', 'BLACK', 'RED'];
    const resultados = criarResultadosCronologicos([
        ...padrao,
        'GREEN',
        ...padrao,
    ]);
    const analiseCompleta = analisarPadraoAoVivo(resultados, 9);
    const analiseReduzida = analisarPadraoAoVivo(resultados, 4);

    assert.equal(analiseCompleta.totalOcorrencias, 1);
    assert.equal(analiseCompleta.resultadosPosPadrao.length, 1);
    assert.equal(analiseReduzida.totalRegistros, 4);
    assert.equal(analiseReduzida.totalOcorrencias, 0);
    assert.equal(analiseReduzida.resultadosPosPadrao.length, 0);
});

test('permite analisar padrões de três, quatro ou cinco pedras', () => {
    const padraoTres = ['BLACK', 'BLUE', 'GREEN'];
    const resultados = criarResultadosCronologicos([
        ...padraoTres,
        'RED',
        'BLUE',
        ...padraoTres,
    ]);
    const analiseTres = analisarPadraoAoVivo(resultados, 500, 3);
    const analiseQuatro = analisarPadraoAoVivo(resultados, 250, 4);
    const analiseCinco = analisarPadraoAoVivo(resultados, 250, 5);

    assert.equal(analiseTres.tamanhoPadrao, 3);
    assert.deepEqual(analiseTres.padraoAtual, padraoTres);
    assert.equal(analiseTres.totalOcorrencias, 1);
    assert.equal(analiseTres.distribuicao.find((item) => item.cor === 'RED').total, 1);
    assert.equal(analiseQuatro.tamanhoPadrao, 4);
    assert.equal(analiseQuatro.padraoAtual.length, 4);
    assert.equal(analiseCinco.tamanhoPadrao, 5);
    assert.equal(analiseCinco.padraoAtual.length, 5);
});

test('usa quatro pedras quando o tamanho solicitado não é permitido', () => {
    const analise = analisarPadraoAoVivo(
        criarResultadosCronologicos(['BLACK', 'BLUE', 'RED', 'GREEN', 'BLACK', 'RED']),
        500,
        6,
    );

    assert.equal(analise.tamanhoPadrao, 4);
    assert.equal(analise.padraoAtual.length, 4);
});

test('limita o histórico pós-padrão às cinco ocorrências mais recentes', () => {
    const padrao = ['BLACK', 'BLUE', 'RED'];
    const cores = [];

    for (let indice = 0; indice < 7; indice += 1) {
        cores.push(...padrao, indice % 2 === 0 ? 'GREEN' : 'BLUE');
    }

    cores.push(...padrao);

    const analise = analisarPadraoAoVivo(criarResultadosCronologicos(cores), 2000, 3);

    assert.equal(analise.resultadosPosPadrao.length, 5);
    assert.deepEqual(
        analise.resultadosPosPadrao.map((resultado) => resultado.gameId),
        [12, 16, 20, 24, 28],
    );
});
