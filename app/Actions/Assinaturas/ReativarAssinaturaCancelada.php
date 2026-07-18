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

class ReativarAssinaturaCancelada
{
    /**
     * @return array{assinatura: Assinatura, pagamento: Pagamento|null}
     *
     * @throws ValidationException
     */
    public function executar(
        Assinatura $assinatura,
        bool $confirmada,
        CarbonInterface $inicioEm,
        int $duracaoDias,
        ?string $valorRecebido,
        ?CarbonInterface $dataPagamento,
        ?string $formaPagamento,
        string $observacao,
        User $responsavel,
    ): array {
        $possuiPagamento = $valorRecebido !== null;

        Validator::make([
            'confirmada' => $confirmada,
            'duracao_dias' => $duracaoDias,
            'valor_recebido' => $valorRecebido,
            'forma_pagamento' => $formaPagamento,
            'observacao' => $observacao,
        ], [
            'confirmada' => ['accepted'],
            'duracao_dias' => ['required', 'integer', 'min:1'],
            'valor_recebido' => [
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0.01',
            ],
            'forma_pagamento' => [
                Rule::requiredIf($possuiPagamento),
                'nullable',
                'string',
                Rule::in(Pagamento::FORMAS_PAGAMENTO),
            ],
            'observacao' => ['required', 'string', 'min:10', 'max:2000'],
        ])->validate();

        if ($possuiPagamento && $dataPagamento === null) {
            $this->falhar(
                'data_pagamento',
                'A data do pagamento é obrigatória na reativação paga.',
            );
        }

        if (! $possuiPagamento && ($dataPagamento || $formaPagamento)) {
            $this->falhar(
                'valor_recebido',
                'Informe o valor recebido ou remova os dados de pagamento para registrar uma cortesia.',
            );
        }

        return DB::transaction(function () use (
            $assinatura,
            $inicioEm,
            $duracaoDias,
            $valorRecebido,
            $dataPagamento,
            $formaPagamento,
            $observacao,
            $responsavel,
            $possuiPagamento,
        ): array {
            $assinaturaAtual = Assinatura::query()
                ->whereKey($assinatura->getKey())
                ->lockForUpdate()
                ->first();

            if (! $assinaturaAtual) {
                $this->falhar('assinatura', 'A assinatura informada não existe.');
            }

            if (! $assinaturaAtual->estaCancelada()) {
                $this->falhar(
                    'assinatura',
                    'Somente uma assinatura cancelada pode ser reativada.',
                );
            }

            $clienteExiste = User::query()
                ->whereKey($assinaturaAtual->user_id)
                ->exists();

            if (! $clienteExiste) {
                $this->falhar(
                    'assinatura',
                    'Não é possível reativar a assinatura de um cliente excluído.',
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
                    'Somente administradores e analistas podem reativar assinaturas.',
                );
            }

            $novoInicio = CarbonImmutable::instance($inicioEm);
            $novoTermino = $novoInicio->addDays($duracaoDias);
            $registroReativacao = sprintf(
                '[Reativação em %s por %s] Período anterior: %s até %s; cancelada em %s. Novo período: %s até %s. %s',
                now()->format('d/m/Y H:i:s'),
                $responsavelAtual->name,
                $assinaturaAtual->inicio_em->format('d/m/Y H:i:s'),
                $assinaturaAtual->termino_em->format('d/m/Y H:i:s'),
                $assinaturaAtual->cancelada_em->format('d/m/Y H:i:s'),
                $novoInicio->format('d/m/Y H:i:s'),
                $novoTermino->format('d/m/Y H:i:s'),
                trim($observacao),
            );
            $assinaturaAtual->inicio_em = $novoInicio;
            $assinaturaAtual->termino_em = $novoTermino;
            $assinaturaAtual->duracao_dias = $duracaoDias;
            $assinaturaAtual->cancelada_em = null;
            $assinaturaAtual->observacao = collect([
                $assinaturaAtual->observacao,
                $registroReativacao,
            ])->filter()->implode(PHP_EOL);
            $assinaturaAtual->save();

            $pagamento = null;

            if ($possuiPagamento) {
                $pagamento = $assinaturaAtual->pagamentos()->create([
                    'user_id' => $assinaturaAtual->user_id,
                    'valor' => $valorRecebido,
                    'data_pagamento' => CarbonImmutable::instance($dataPagamento),
                    'tipo' => 'renovacao',
                    'forma_pagamento' => $formaPagamento,
                    'registrado_por' => $responsavelAtual->getKey(),
                    'observacao' => $registroReativacao,
                ]);
            }

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
