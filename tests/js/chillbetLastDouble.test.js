import assert from 'node:assert/strict';
import test from 'node:test';
import { analisarAzul } from '../../resources/js/analises/analisarAzul.js';
import { analisarPreto } from '../../resources/js/analises/analisarPreto.js';
import { analisarVermelho } from '../../resources/js/analises/analisarVermelho.js';

function resultado(gameId, cor) {
    return {
        gameId,
        cor,
        data: `2026-07-14 10:${String(gameId).padStart(2, '0')}:00`,
    };
}

for (const [cor, analisar, campo] of [
    ['BLUE', analisarAzul, 'ultimoAzulDuploEm'],
    ['BLACK', analisarPreto, 'ultimoPretoDuploEm'],
    ['RED', analisarVermelho, 'ultimoVermelhoDuploEm'],
]) {
    test(`${cor}: informa a confirmação do último duplo mesmo com resultados posteriores da cor`, () => {
        const analise = analisar([
            resultado(10, cor),
            resultado(9, 'GREEN'),
            resultado(8, cor),
            resultado(7, cor),
            resultado(6, cor),
            resultado(5, 'GREEN'),
            resultado(4, cor),
            resultado(3, cor),
        ]);

        assert.equal(analise[campo].toISOString(), '2026-07-14T13:07:00.000Z');
    });

    test(`${cor}: retorna nulo quando não há duplo na base selecionada`, () => {
        const registros = [
            resultado(5, cor),
            resultado(4, 'GREEN'),
            resultado(3, cor),
            resultado(2, cor),
        ];

        assert.equal(analisar([])[campo], null);
        assert.equal(analisar(registros, 3)[campo], null);
        assert.equal(analisar(registros)[campo].toISOString(), '2026-07-14T13:03:00.000Z');
    });
}
