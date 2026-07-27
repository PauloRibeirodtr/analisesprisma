<?php

namespace App\Console\Commands;

use App\Actions\DoubleBranco\SincronizarJanelasPagamentoCrash;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('double:sincronizar-janelas')]
#[Description('Sincroniza as janelas de pagamento Crash no Análise Prisma')]
class SincronizarJanelasPagamentoCrashCommand extends Command
{
    public function handle(
        SincronizarJanelasPagamentoCrash $sincronizar,
    ): int {
        $quantidade = $sincronizar->handle();

        $this->info("{$quantidade} janelas sincronizadas.");

        return self::SUCCESS;
    }
}
