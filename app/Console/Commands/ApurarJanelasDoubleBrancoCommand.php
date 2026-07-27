<?php

namespace App\Console\Commands;

use App\Services\ApuracaoJanelaDoubleBrancoService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('double:apurar-janelas')]
#[Description('Apura tiros, wins e losses das janelas do Double Branco')]
class ApurarJanelasDoubleBrancoCommand extends Command
{
    public function handle(
        ApuracaoJanelaDoubleBrancoService $apuracao,
    ): int {
        $quantidade = $apuracao->apurar();

        $this->info("{$quantidade} janelas apuradas.");

        return self::SUCCESS;
    }
}
