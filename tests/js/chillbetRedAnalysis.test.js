import assert from 'node:assert/strict';
import test from 'node:test';
import { analisarVermelho } from '../../resources/js/analises/analisarVermelho.js';

function resultado(gameId, cor, minuto) {
    return {
        gameId,
        cor,
        data: `2026-07-14 10:${String(minuto).padStart(2, '0')}:00`,
    };
}

test('calcula vermelhos isolados e intervalos entre vermelhos duplos', () => {
    const analise = analisarVermelho([
        resultado(14, 'RED', 22),
        resultado(13, 'RED', 20),
        resultado(12, 'BLUE', 18),
        resultado(11, 'RED', 15),
        resultado(10, 'BLACK', 12),
        resultado(9, 'RED', 10),
        resultado(8, 'RED', 8),
        resultado(7, 'BLUE', 7),
        resultado(6, 'RED', 6),
        resultado(5, 'BLUE', 5),
        resultado(4, 'RED', 4),
        resultado(3, 'BLACK', 3),
        resultado(2, 'RED', 2),
        resultado(1, 'RED', 1),
    ]);

    assert.equal(analise.totalVermelhosIsolados, 3);
    assert.equal(analise.vermelhosIsoladosAtuais, 0);
    assert.equal(analise.totalVermelhosDuplos, 3);
    assert.equal(analise.maximoVermelhosSemDuplo, 2);
    assert.equal(analise.mediaVermelhosSemDuplo, 1.5);
    assert.equal(analise.maximoMinutosSemDuplo, 12);
    assert.equal(analise.mediaMinutosSemDuplo, 10);
    assert.equal(analise.ultimoVermelhoEm.getMinutes(), 22);
});

test('mantém apenas os vermelhos isolados do ciclo atual', () => {
    const analise = analisarVermelho([
        resultado(7, 'BLUE', 7),
        resultado(6, 'RED', 6),
        resultado(5, 'BLUE', 5),
        resultado(4, 'RED', 4),
        resultado(3, 'BLUE', 3),
        resultado(2, 'RED', 2),
        resultado(1, 'RED', 1),
    ]);

    assert.equal(analise.totalVermelhosIsolados, 2);
    assert.equal(analise.vermelhosIsoladosAtuais, 2);
    assert.equal(analise.totalVermelhosDuplos, 1);
    assert.equal(analise.maximoVermelhosSemDuplo, 2);
    assert.equal(analise.maximoMinutosSemDuplo, 5);
});

test('conta uma sequência de vermelhos como apenas um duplo e ignora as bordas', () => {
    const analise = analisarVermelho([
        resultado(8, 'RED', 8),
        resultado(7, 'BLUE', 7),
        resultado(6, 'RED', 6),
        resultado(5, 'RED', 5),
        resultado(4, 'RED', 4),
        resultado(3, 'RED', 3),
        resultado(2, 'BLUE', 2),
        resultado(1, 'RED', 1),
    ]);

    assert.equal(analise.totalVermelhosDuplos, 1);
    assert.equal(analise.totalVermelhosIsolados, 0);
    assert.equal(analise.vermelhosIsoladosAtuais, 0);
});
