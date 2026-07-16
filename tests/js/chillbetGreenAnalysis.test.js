import assert from 'node:assert/strict';
import test from 'node:test';
import { analisarVerde } from '../../resources/js/analises/analisarVerde.js';

function resultado(gameId, cor, minuto) {
    return {
        gameId,
        cor,
        data: `2026-07-15 10:${String(minuto).padStart(2, '0')}:00`,
    };
}

test('calcula as distâncias entre todos os verdes', () => {
    const analise = analisarVerde([
        resultado(9, 'BLACK', 9),
        resultado(8, 'GREEN', 8),
        resultado(7, 'RED', 7),
        resultado(6, 'BLUE', 6),
        resultado(5, 'GREEN', 5),
        resultado(4, 'GREEN', 4),
        resultado(3, 'BLACK', 3),
        resultado(2, 'RED', 2),
        resultado(1, 'GREEN', 1),
    ]);

    assert.equal(analise.totalVerdes, 4);
    assert.equal(analise.totalIntervalos, 3);
    assert.equal(analise.maximaDistanciaRodadas, 2);
    assert.equal(analise.mediaDistanciaRodadas, 4 / 3);
    assert.equal(analise.maximaDistanciaMinutos, 3);
    assert.equal(analise.mediaDistanciaMinutos, 7 / 3);
    assert.equal(analise.ultimoVerdeEm.getMinutes(), 8);
});

test('considera distância zero entre verdes consecutivos e contabiliza ambos', () => {
    const analise = analisarVerde([
        resultado(3, 'RED', 3),
        resultado(2, 'GREEN', 2),
        resultado(1, 'GREEN', 1),
    ]);

    assert.equal(analise.totalVerdes, 2);
    assert.equal(analise.totalIntervalos, 1);
    assert.equal(analise.maximaDistanciaRodadas, 0);
    assert.equal(analise.mediaDistanciaRodadas, 0);
    assert.equal(analise.maximaDistanciaMinutos, 1);
    assert.equal(analise.mediaDistanciaMinutos, 1);
});

test('não calcula distância quando existe menos de dois verdes', () => {
    const analise = analisarVerde([
        resultado(2, 'RED', 2),
        resultado(1, 'GREEN', 1),
    ]);

    assert.equal(analise.totalVerdes, 1);
    assert.equal(analise.totalIntervalos, 0);
    assert.equal(analise.maximaDistanciaRodadas, null);
    assert.equal(analise.mediaDistanciaRodadas, null);
    assert.equal(analise.maximaDistanciaMinutos, null);
    assert.equal(analise.mediaDistanciaMinutos, null);
});
