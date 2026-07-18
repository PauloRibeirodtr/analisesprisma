<?php

namespace App\Actions\Assinaturas;

use App\Models\Assinatura;
use App\Models\Pagamento;
use App\Models\Plano;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContratarPlanoManualmente
{
    /**
     * @return array{assinatura: Assinatura, pagamento: Pagamento}
     *
     * @throws ValidationException
     */
    public function executar(
        User $cliente,
        Plano $plano,
        CarbonInterface $inicioEm,
        int $duracaoDias,
        string $valorPago,
        CarbonInterface $dataPagamento,
        string $formaPagamento,
        ?string $observacao,
        User $responsavel,
    ): array {
        Validator::make([
            'duracao_dias' => $duracaoDias,
            'valor_pago' => $valorPago,
            'forma_pagamento' => $formaPagamento,
        ], [
            'duracao_dias' => ['required', 'integer', 'min:1'],
            'valor_pago' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'forma_pagamento' => [
                'required',
                'string',
                Rule::in(Pagamento::FORMAS_PAGAMENTO),
            ],
        ])->validate();

        return DB::transaction(function () use (
            $cliente,
            $plano,
            $inicioEm,
            $duracaoDias,
            $valorPago,
            $dataPagamento,
            $formaPagamento,
            $observacao,
            $responsavel,
        ): array {
            $clienteAtual = User::query()
                ->whereKey($cliente->getKey())
                ->lockForUpdate()
                ->first();

            if (! $clienteAtual || $clienteAtual->perfil !== 'cliente') {
                $this->falhar(
                    'cliente',
                    'O cliente informado não existe ou não possui o perfil cliente.',
                );
            }

            $planoAtual = Plano::query()
                ->whereKey($plano->getKey())
                ->lockForUpdate()
                ->first();

            if (! $planoAtual) {
                $this->falhar('plano', 'O plano informado não existe.');
            }

            if (! $planoAtual->disponivel_para_venda) {
                $this->falhar(
                    'plano',
                    'O plano informado não está disponível para venda.',
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
                    'Somente administradores e analistas podem registrar contratações.',
                );
            }

            $possuiMesmoPlanoVigente = $clienteAtual->assinaturas()
                ->vigentes()
                ->where('plano_id', $planoAtual->getKey())
                ->exists();

            if ($possuiMesmoPlanoVigente) {
                $this->falhar(
                    'plano',
                    'O cliente já possui acesso vigente a este plano. Utilize a operação de renovação.',
                );
            }

            $inicio = CarbonImmutable::instance($inicioEm);
            $assinatura = $clienteAtual->assinaturas()->create([
                'plano_id' => $planoAtual->getKey(),
                'nome_plano' => $planoAtual->nome,
                'valor_referencia' => $planoAtual->valor_padrao,
                'duracao_dias' => $duracaoDias,
                'inicio_em' => $inicio,
                'termino_em' => $inicio->addDays($duracaoDias),
                'cancelada_em' => null,
                'observacao' => $observacao,
            ]);

            $pagamento = $assinatura->pagamentos()->create([
                'user_id' => $clienteAtual->getKey(),
                'valor' => $valorPago,
                'data_pagamento' => CarbonImmutable::instance($dataPagamento),
                'tipo' => 'contratacao',
                'forma_pagamento' => $formaPagamento,
                'registrado_por' => $responsavelAtual->getKey(),
                'observacao' => $observacao,
            ]);

            return [
                'assinatura' => $assinatura,
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
