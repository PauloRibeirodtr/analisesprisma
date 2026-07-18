<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Assinaturas\ReativarAssinaturaCancelada;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReativacaoRequest;
use App\Models\Assinatura;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClienteAssinaturaReativacaoController extends Controller
{
    public function create(
        User $cliente,
        Assinatura $assinatura,
    ): View|RedirectResponse {
        Gate::authorize('administrar-clientes');
        abort_unless($cliente->perfil === 'cliente', 404);

        if (! $assinatura->estaCancelada()) {
            return redirect()
                ->route('admin.clientes.show', $cliente)
                ->with(
                    'erro',
                    'Somente assinaturas canceladas podem ser reativadas.',
                );
        }

        return view('admin.clientes.assinaturas.reativacao', compact(
            'assinatura',
            'cliente',
        ));
    }

    public function store(
        StoreReativacaoRequest $request,
        User $cliente,
        Assinatura $assinatura,
        ReativarAssinaturaCancelada $reativarAssinatura,
    ): RedirectResponse {
        $dados = $request->validated();
        $registrarPagamento = (bool) $dados['registrar_pagamento'];

        $reativarAssinatura->executar(
            assinatura: $assinatura,
            confirmada: (bool) $dados['confirmada'],
            inicioEm: CarbonImmutable::parse($dados['inicio_em']),
            duracaoDias: (int) $dados['duracao_dias'],
            valorRecebido: $registrarPagamento
                ? (string) $dados['valor_recebido']
                : null,
            dataPagamento: $registrarPagamento
                ? CarbonImmutable::parse($dados['data_pagamento'])
                : null,
            formaPagamento: $registrarPagamento
                ? $dados['forma_pagamento']
                : null,
            observacao: $dados['observacao'],
            responsavel: $request->user(),
        );

        return redirect()
            ->route('admin.clientes.show', $cliente)
            ->with('sucesso', $registrarPagamento
                ? 'Assinatura reativada e pagamento registrado com sucesso.'
                : 'Assinatura reativada como cortesia, sem lançamento financeiro.');
    }
}
