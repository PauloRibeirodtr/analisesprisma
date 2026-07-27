import assert from 'node:assert/strict';
import test from 'node:test';
import {
    corEsperadaDouble,
    detalhesCorDouble,
    imagemResultadoDouble,
    normalizarResultadoDouble,
} from '../../resources/js/composables/useBlazeDoubleFeed.js';

test('normaliza um resultado válido do Double', () => {
    assert.deepEqual(normalizarResultadoDouble({
        gameId: 'game-123',
        color: 0,
        roll: 0,
        createdAt: '2026-07-26T03:12:34.123Z',
    }), {
        gameId: 'game-123',
        color: 0,
        roll: 0,
        createdAt: '2026-07-26T03:12:34.123Z',
    });
});

test('rejeita resultados inválidos ou com cor incompatível', () => {
    assert.equal(normalizarResultadoDouble(null), null);
    assert.equal(normalizarResultadoDouble({
        gameId: '',
        color: 0,
        roll: 0,
        createdAt: '2026-07-26T03:12:34.123Z',
    }), null);
    assert.equal(normalizarResultadoDouble({
        gameId: 'game-123',
        color: 2,
        roll: 0,
        createdAt: '2026-07-26T03:12:34.123Z',
    }), null);
    assert.equal(normalizarResultadoDouble({
        gameId: 'game-123',
        color: 1,
        roll: 15,
        createdAt: 'invalid',
    }), null);
});

test('mapeia as faixas de cor do Double', () => {
    assert.equal(corEsperadaDouble(0), 0);
    assert.equal(corEsperadaDouble(1), 1);
    assert.equal(corEsperadaDouble(7), 1);
    assert.equal(corEsperadaDouble(8), 2);
    assert.equal(corEsperadaDouble(14), 2);
    assert.equal(corEsperadaDouble(15), null);

    assert.equal(detalhesCorDouble(0).nome, 'Branco');
    assert.equal(detalhesCorDouble(0).destaque, 'Pagamento 14x');
    assert.equal(detalhesCorDouble(4).nome, 'Vermelho');
    assert.equal(detalhesCorDouble(12).nome, 'Preto');
});

test('gera somente caminhos seguros para imagens conhecidas', () => {
    assert.equal(imagemResultadoDouble(0), '/img/double/0.png');
    assert.equal(imagemResultadoDouble(14), '/img/double/14.png');
    assert.equal(imagemResultadoDouble(15), null);
    assert.equal(imagemResultadoDouble('../secret'), null);
});
