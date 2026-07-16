import assert from 'node:assert/strict';
import test from 'node:test';
import { analisarPreto } from '../../resources/js/analises/analisarPreto.js';

function resultado(gameId, cor, minuto) {
    return {
        gameId,
        cor,
        data: `2026-07-14 10:${String(minuto).padStart(2, '0')}:00`,
    };
}

test('conta uma sequência grande de pretos como apenas um preto duplo', () => {
    const analise = analisarPreto([
        resultado(15, 'BLACK', 15),
        resultado(14, 'BLUE', 14),
        resultado(13, 'BLACK', 13),
        resultado(12, 'RED', 12),
        resultado(11, 'BLACK', 11),
        resultado(10, 'BLACK', 10),
        resultado(9, 'BLACK', 9),
        resultado(8, 'BLUE', 8),
        resultado(7, 'BLACK', 7),
        resultado(6, 'RED', 6),
        resultado(5, 'BLACK', 5),
        resultado(4, 'BLACK', 4),
        resultado(3, 'BLACK', 3),
        resultado(2, 'BLACK', 2),
        resultado(1, 'BLUE', 1),
    ]);

    assert.equal(analise.totalPretosDuplos, 2);
    assert.equal(analise.totalPretosIsolados, 2);
    assert.equal(analise.pretosIsoladosAtuais, 1);
    assert.equal(analise.maximoPretosSemDuplo, 1);
    assert.equal(analise.mediaPretosSemDuplo, 1);
    assert.equal(analise.maximoMinutosSemDuplo, 7);
    assert.equal(analise.mediaMinutosSemDuplo, 7);
    assert.equal(analise.ultimoPretoEm.getMinutes(), 15);
});

test('não classifica como isolado um preto sem vizinhos nas bordas da janela', () => {
    const analise = analisarPreto([
        resultado(5, 'BLACK', 5),
        resultado(4, 'BLUE', 4),
        resultado(3, 'BLACK', 3),
        resultado(2, 'RED', 2),
        resultado(1, 'BLACK', 1),
    ]);

    assert.equal(analise.totalPretosDuplos, 0);
    assert.equal(analise.totalPretosIsolados, 1);
    assert.equal(analise.pretosIsoladosAtuais, 1);
    assert.equal(analise.maximoPretosSemDuplo, 1);
    assert.equal(analise.maximoMinutosSemDuplo, null);
});
