<?php

use App\Services\LeituraHistoricoDoubleService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config([
        'database.connections.historico' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'double.historico_timezone' => 'America/Sao_Paulo',
    ]);

    DB::purge('historico');
    DB::connection('historico')->statement(
        'CREATE TABLE blaze (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            idblaze TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            color INTEGER NOT NULL,
            roll INTEGER NOT NULL
        )',
    );
});

afterEach(function () {
    DB::disconnect('historico');
    DB::purge('historico');
});

it('consulta o histórico em São Paulo, devolve UTC e ignora duplicatas', function () {
    DB::connection('historico')->table('blaze')->insert([
        [
            'idblaze' => 'fora-da-janela',
            'created_at' => '2026-07-27 09:59:59',
            'color' => 1,
            'roll' => 5,
        ],
        [
            'idblaze' => 'resultado-1',
            'created_at' => '2026-07-27 10:00:30',
            'color' => 1,
            'roll' => 3,
        ],
        [
            'idblaze' => 'resultado-1',
            'created_at' => '2026-07-27 10:00:30',
            'color' => 1,
            'roll' => 3,
        ],
        [
            'idblaze' => 'resultado-2',
            'created_at' => '2026-07-27 10:01:00',
            'color' => 0,
            'roll' => 0,
        ],
    ]);

    $resultados = app(LeituraHistoricoDoubleService::class)->buscarEntre(
        CarbonImmutable::parse('2026-07-27T13:00:00Z'),
        CarbonImmutable::parse('2026-07-27T13:02:59Z'),
    );

    expect($resultados)
        ->toHaveCount(2)
        ->and($resultados->pluck('idblaze')->all())
        ->toBe(['resultado-1', 'resultado-2'])
        ->and($resultados->last()['resultado_em']->toISOString())
        ->toBe('2026-07-27T13:01:00.000000Z');
});
