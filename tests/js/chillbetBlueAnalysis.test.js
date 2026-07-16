import assert from 'node:assert/strict';
import test from 'node:test';
import { analisarAzul } from '../../resources/js/analises/analisarAzul.js';

function resultado(gameId, cor, minuto) {
    return {
        gameId,
        cor,
        data: `2026-07-14 10:${String(minuto).padStart(2, '0')}:00`,
    };
}

test('calcula azuis isolados e intervalos entre azuis duplos', () => {
    const analise = analisarAzul([
        resultado(14, 'BLUE', 22),
        resultado(13, 'BLUE', 20),
        resultado(12, 'RED', 18),
        resultado(11, 'BLUE', 15),
        resultado(10, 'BLACK', 12),
        resultado(9, 'BLUE', 10),
        resultado(8, 'BLUE', 8),
        resultado(7, 'RED', 7),
        resultado(6, 'BLUE', 6),
        resultado(5, 'RED', 5),
        resultado(4, 'BLUE', 4),
        resultado(3, 'BLACK', 3),
        resultado(2, 'BLUE', 2),
        resultado(1, 'BLUE', 1),
    ], 250);

    assert.equal(analise.totalAzuisIsolados, 3);
    assert.equal(analise.azuisIsoladosAtuais, 0);
    assert.equal(analise.totalAzuisDuplos, 3);
    assert.equal(analise.maximoAzuisSemDuplo, 2);
    assert.equal(analise.mediaAzuisSemDuplo, 1.5);
    assert.equal(analise.maximoMinutosSemDuplo, 12);
    assert.equal(analise.mediaMinutosSemDuplo, 10);
    assert.equal(analise.ultimoAzulEm.getMinutes(), 22);
});

test('não cria estatísticas de intervalo sem dois azuis duplos', () => {
    const analise = analisarAzul([
        resultado(3, 'BLUE', 3),
        resultado(2, 'RED', 2),
        resultado(1, 'BLUE', 1),
    ]);

    assert.equal(analise.totalAzuisIsolados, 0);
    assert.equal(analise.azuisIsoladosAtuais, 0);
    assert.equal(analise.totalAzuisDuplos, 0);
    assert.equal(analise.maximoAzuisSemDuplo, 0);
    assert.equal(analise.maximoMinutosSemDuplo, null);
    assert.equal(analise.mediaAzuisSemDuplo, null);
    assert.equal(analise.mediaMinutosSemDuplo, null);
});

test('conta uma sequência de azuis como apenas um azul duplo', () => {
    const analise = analisarAzul([
        resultado(5, 'RED', 5),
        resultado(4, 'BLUE', 4),
        resultado(3, 'BLUE', 3),
        resultado(2, 'BLUE', 2),
        resultado(1, 'BLUE', 1),
    ]);

    assert.equal(analise.totalAzuisDuplos, 1);
    assert.equal(analise.totalAzuisIsolados, 0);
    assert.equal(analise.azuisIsoladosAtuais, 0);
});

test('conta os azuis isolados atuais desde o último azul duplo', () => {
    const analise = analisarAzul([
        resultado(7, 'RED', 7),
        resultado(6, 'BLUE', 6),
        resultado(5, 'RED', 5),
        resultado(4, 'BLUE', 4),
        resultado(3, 'RED', 3),
        resultado(2, 'BLUE', 2),
        resultado(1, 'BLUE', 1),
    ]);

    assert.equal(analise.totalAzuisIsolados, 2);
    assert.equal(analise.azuisIsoladosAtuais, 2);
    assert.equal(analise.maximoAzuisSemDuplo, 2);
    assert.equal(analise.maximoMinutosSemDuplo, 5);
});

test('inclui o ciclo atual quando ele supera as máximas encerradas', () => {
    const analise = analisarAzul([
        resultado(12, 'RED', 14),
        resultado(11, 'BLUE', 11),
        resultado(10, 'RED', 10),
        resultado(9, 'BLUE', 9),
        resultado(8, 'RED', 8),
        resultado(7, 'BLUE', 7),
        resultado(6, 'BLUE', 6),
        resultado(5, 'RED', 5),
        resultado(4, 'BLUE', 4),
        resultado(3, 'RED', 3),
        resultado(2, 'BLUE', 2),
        resultado(1, 'BLUE', 1),
    ]);

    assert.equal(analise.azuisIsoladosAtuais, 2);
    assert.equal(analise.maximoAzuisSemDuplo, 2);
    assert.equal(analise.maximoMinutosSemDuplo, 7);
    assert.equal(analise.mediaAzuisSemDuplo, 1);
    assert.equal(analise.mediaMinutosSemDuplo, 5);
});
