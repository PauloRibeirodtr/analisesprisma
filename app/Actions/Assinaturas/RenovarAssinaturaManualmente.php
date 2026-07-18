<?php

namespace App\Actions\Assinaturas;

use App\Models\Assinatura;
use App\Models\Pagamento;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RenovarAssinaturaManualmente
{
    /**
     * @return array{assinatura: Assinatura, pagamento: Pagamento}
     *
     * @throws ValidationException
     */
    public function executar(
        Assinatura $assinatura,
        int $diasAdicionados,
        string $valorRecebido,
        CarbonInterface $dataPagamento,
        string $formaPagamento,
        ?string $observacao,
        User $responsavel,
        ?CarbonInterface $reativacaoEm = null,
    ): array {
        Validator::make([
            'dias_adicionados' => $diasAdicionados,
            'valor_recebido' => $valorRecebido,
            'forma_pagamento' => $formaPagamento,
        ], [
            'dias_adicionados' => ['required', 'integer', 'min:1'],
            'valor_recebido' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'forma_pagamento' => [
                'required',
                'string',
                Rule::in(Pagamento::FORMAS_PAGAMENTO),
            ],
        ])->validate();

        return DB::transaction(function () use (
            $assinatura,
            $diasAdicionados,
            $valorRecebido,
            $dataPagamento,
            $formaPagamento,
            $observacao,
            $responsavel,
            $reativacaoEm,
        ): array {
            $assinaturaAtual = Assinatura::query()
                ->whereKey($assinatura->getKey())
                ->lockForUpdate()
                ->first();

            if (! $assinaturaAtual) {
                $this->falhar('assinatura', 'A assinatura informada não existe.');
            }

            $clienteExiste = User::query()
                ->whereKey($assinaturaAtual->user_id)
                ->exists();

            if (! $clienteExiste) {
                $this->falhar(
                    'assinatura',
                    'Não é possível renovar a assinatura de um cliente excluído.',
                );
            }

            $responsavelAtual = User::query()
                ->whereKey($responsavel->getKey())
                ->first();

            if (
                ! $responsavelAtual
                || ! in_array(
                    $responsavelAtual->perfil,
                    User::PERFIS_ACESSO_IRRESTRITO,
                    true,
                )
            ) {
                $this->falhar(
                    'responsavel',
                    'Somente administradores e analistas podem registrar renovações.',
                );
            }

            if ($assinaturaAtual->estaCancelada()) {
                $this->falhar(
                    'assinatura',
                    'A assinatura está cancelada e exige uma reativação explícita.',
                );
            }

            if ($assinaturaAtual->inicio_em->gt(now())) {
                $this->falhar(
                    'assinatura',
                    'Uma assinatura futura não pode ser renovada antes de iniciar.',
                );
            }

            if ($assinaturaAtual->estaVencida()) {
                $novoInicio = $reativacaoEm
                    ? CarbonImmutable::instance($reativacaoEm)
                    : CarbonImmutable::instance(now());

                $assinaturaAtual->inicio_em = $novoInicio;
                $assinaturaAtual->termino_em = $novoInicio->addDays($diasAdicionados);
            } else {
                $terminoAtual = CarbonImmutable::instance(
                    $assinaturaAtual->termino_em,
                );
                $assinaturaAtual->termino_em = $terminoAtual
                    ->addDays($diasAdicionados);
            }

            $assinaturaAtual->save();

            $pagamento = $assinaturaAtual->pagamentos()->create([
                'user_id' => $assinaturaAtual->user_id,
                'valor' => $valorRecebido,
                'data_pagamento' => CarbonImmutable::instance($dataPagamento),
                'tipo' => 'renovacao',
                'forma_pagamento' => $formaPagamento,
                'registrado_por' => $responsavelAtual->getKey(),
                'observacao' => $observacao,
            ]);

            return [
                'assinatura' => $assinaturaAtual,
                'pagamento' => $pagamento,
            ];
        });
    }

    /**
     * @throws ValidationException
     */
    private function falhar(string $campo, string $mensagem): never
    {
        throw ValidationException::withMessages([
            $campo => $mensagem,
        ]);
    }
}
