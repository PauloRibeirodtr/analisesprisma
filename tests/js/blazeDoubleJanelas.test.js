import assert from 'node:assert/strict';
import test from 'node:test';
import {
    normalizarJanelaPagamento,
    normalizarPainelJanelas,
} from '../../resources/js/composables/useBlazeDoubleJanelas.js';

test('normaliza os dados recebidos da API local', () => {
    const janela = normalizarJanelaPagamento({
        id: 15,
        inicioEm: '2026-07-27T12:00:00.000Z',
        fimEm: '2026-07-27T12:02:59.000Z',
        horarioAlvoEm: '2026-07-27T12:01:00.000Z',
        status: 'win',
        tirosNaJanela: 4,
        tirosAcumuladosCiclo: 10,
        fonteIndisponivel: false,
    });

    assert.deepEqual(janela, {
        id: 15,
        origemId: '',
        inicioEm: '2026-07-27T12:00:00.000Z',
        fimEm: '2026-07-27T12:02:59.000Z',
        horarioAlvoEm: '2026-07-27T12:01:00.000Z',
        status: 'win',
        ordemNoCiclo: null,
        tirosNaJanela: 4,
        tirosAcumuladosCiclo: 10,
        tirosNoCicloAtual: null,
        fonteIndisponivel: false,
        posicaoAnterior: null,
    });
});

test('normaliza as coleções e o ciclo atual do painel', () => {
    const payload = normalizarPainelJanelas({
        proximas: Array.from({ length: 3 }, (_, index) => ({
            id: index + 1,
            status: 'agendada',
            inicioEm: '2026-07-27T12:00:00.000Z',
            fimEm: '2026-07-27T12:02:59.000Z',
            horarioAlvoEm: '2026-07-27T12:01:00.000Z',
        })),
        anteriores: Array.from({ length: 5 }, (_, index) => ({
            id: index + 10,
            status: 'loss',
            inicioEm: '2026-07-27T11:00:00.000Z',
            fimEm: '2026-07-27T11:02:59.000Z',
            horarioAlvoEm: '2026-07-27T11:01:00.000Z',
        })),
        cicloAtual: {
            id: 9,
            ordemJanela: 3,
            totalTiros: 30,
            sequenciaLosses: 2,
        },
    });

    assert.equal(payload.proximas.length, 3);
    assert.equal(payload.anteriores.length, 5);
    assert.deepEqual(payload.cicloAtual, {
        id: 9,
        ordemJanela: 3,
        totalTiros: 30,
        sequenciaLosses: 2,
    });
    assert.deepEqual(payload.recuperacao, {
        ativa: false,
        iniciadaEm: null,
        brancosRecebidos: 0,
        brancosNecessarios: 2,
    });
});

test('normaliza janela cancelada e recuperação ativa', () => {
    const payload = normalizarPainelJanelas({
        proximas: [{
            id: 1,
            status: 'cancelada_recuperacao',
            inicioEm: '2026-07-27T12:00:00.000Z',
            fimEm: '2026-07-27T12:02:59.000Z',
            horarioAlvoEm: '2026-07-27T12:01:00.000Z',
            tirosNaJanela: 3,
            tirosAcumuladosCiclo: 18,
        }],
        anteriores: [],
        recuperacao: {
            ativa: true,
            iniciadaEm: '2026-07-27T12:01:10.000Z',
            brancosRecebidos: 1,
            brancosNecessarios: 2,
        },
    });

    assert.equal(payload.proximas[0].status, 'cancelada_recuperacao');
    assert.deepEqual(payload.recuperacao, {
        ativa: true,
        iniciadaEm: '2026-07-27T12:01:10.000Z',
        brancosRecebidos: 1,
        brancosNecessarios: 2,
    });
});
