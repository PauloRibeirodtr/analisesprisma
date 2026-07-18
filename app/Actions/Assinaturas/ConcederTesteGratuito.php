<?php

namespace App\Actions\Assinaturas;

use App\Models\Assinatura;
use App\Models\Plano;
use App\Models\User;
use InvalidArgumentException;

class ConcederTesteGratuito
{
    public function executar(User $cliente): Assinatura
    {
        $duracaoHoras = (int) config('assinaturas.teste_gratuito_horas');

        if ($duracaoHoras < 1) {
            throw new InvalidArgumentException(
                'A duração do teste gratuito deve ser de pelo menos uma hora.',
            );
        }

        $plano = Plano::query()
            ->where('slug', Plano::SLUG_TESTE)
            ->firstOrFail();
        $inicioEm = now();

        return $cliente->assinaturas()->create([
            'plano_id' => $plano->id,
            'nome_plano' => $plano->nome,
            'valor_referencia' => $plano->valor_padrao,
            'duracao_dias' => max(1, (int) ceil($duracaoHoras / 24)),
            'inicio_em' => $inicioEm,
            'termino_em' => $inicioEm->copy()->addHours($duracaoHoras),
            'cancelada_em' => null,
            'observacao' => 'Teste gratuito concedido automaticamente no autocadastro.',
        ]);
    }
}
