<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Assinaturas\RenovarAssinaturaManualmente;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRenovacaoRequest;
use App\Models\Assinatura;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClienteAssinaturaRenovacaoController extends Controller
{
    public function create(
        User $cliente,
        Assinatura $assinatura,
    ): View|RedirectResponse {
        Gate::authorize('administrar-clientes');
        abort_unless($cliente->perfil === 'cliente', 404);

        if ($assinatura->estaCancelada()) {
            return redirect()->route(
                'admin.clientes.assinaturas.reativacao.create',
                [$cliente, $assinatura],
            );
        }

        if ($assinatura->inicio_em->gt(now())) {
            return redirect()
                ->route('admin.clientes.show', $cliente)
                ->with(
                    'erro',
                    'A assinatura ainda não iniciou e não pode ser renovada.',
                );
        }

        return view('admin.clientes.assinaturas.renovacao', compact(
            'assinatura',
            'cliente',
        ));
    }

    public function store(
        StoreRenovacaoRequest $request,
        User $cliente,
        Assinatura $assinatura,
        RenovarAssinaturaManualmente $renovarAssinatura,
    ): RedirectResponse {
        $dados = $request->validated();

        $renovarAssinatura->executar(
            assinatura: $assinatura,
            diasAdicionados: (int) $dados['dias_adicionados'],
            valorRecebido: (string) $dados['valor_recebido'],
            dataPagamento: CarbonImmutable::parse($dados['data_pagamento']),
            formaPagamento: $dados['forma_pagamento'],
            observacao: $dados['observacao'] ?? null,
            responsavel: $request->user(),
            reativacaoEm: $assinatura->estaVencida()
                ? CarbonImmutable::parse($dados['nova_data_inicio'])
                : null,
        );

        return redirect()
            ->route('admin.clientes.show', $cliente)
            ->with('sucesso', 'Assinatura renovada e pagamento registrado com sucesso.');
    }
}
