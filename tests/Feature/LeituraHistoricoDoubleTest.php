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

it('encontra o gatilho global e os dois brancos não consecutivos', function () {
    $registros = [[
        'idblaze' => 'branco-anterior',
        'created_at' => '2026-07-27 09:59:30',
        'color' => 0,
        'roll' => 0,
    ]];

    foreach (range(1, 35) as $indice) {
        $registros[] = [
            'idblaze' => "sem-branco-{$indice}",
            'created_at' => CarbonImmutable::parse(
                '2026-07-27 10:00:00',
                'America/Sao_Paulo',
            )->addSeconds($indice * 30)->format('Y-m-d H:i:s'),
            'color' => 1,
            'roll' => 1,
        ];
    }

    $registros[] = $registros[10];
    DB::connection('historico')->table('blaze')->insert($registros);

    $leitura = app(LeituraHistoricoDoubleService::class);
    $sequencia = $leitura->buscarAteGatilhoSemBranco(35);
    $gatilho = $sequencia->last();

    DB::connection('historico')->table('blaze')->insert([
        [
            'idblaze' => 'primeiro-branco',
            'created_at' => '2026-07-27 10:18:00',
            'color' => 0,
            'roll' => 0,
        ],
        [
            'idblaze' => 'colorido-entre-brancos',
            'created_at' => '2026-07-27 10:18:30',
            'color' => 2,
            'roll' => 8,
        ],
        [
            'idblaze' => 'segundo-branco',
            'created_at' => '2026-07-27 10:19:00',
            'color' => 0,
            'roll' => 0,
        ],
    ]);
    $brancos = $leitura->buscarBrancosDepois(
        $gatilho['id'],
        $gatilho['resultado_em'],
    );

    expect($sequencia)->toHaveCount(35)
        ->and($gatilho['idblaze'])->toBe('sem-branco-35')
        ->and($brancos->pluck('idblaze')->all())->toBe([
            'primeiro-branco',
            'segundo-branco',
        ]);
});
