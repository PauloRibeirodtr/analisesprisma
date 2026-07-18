<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Assinaturas\ContratarPlanoManualmente;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreContratacaoRequest;
use App\Models\Plano;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClienteAssinaturaController extends Controller
{
    public function create(User $cliente): View
    {
        Gate::authorize('administrar-clientes');
        abort_unless($cliente->perfil === 'cliente', 404);

        $planos = Plano::query()
            ->select([
                'id',
                'nome',
                'slug',
                'descricao',
                'valor_padrao',
                'duracao_dias',
            ])
            ->where('disponivel_para_venda', true)
            ->orderBy('nome')
            ->get();

        $planosVigentes = $cliente->assinaturas()
            ->vigentes()
            ->whereIn('plano_id', $planos->pluck('id'))
            ->pluck('plano_id');

        return view('admin.clientes.assinaturas.create', [
            'cliente' => $cliente,
            'planos' => $planos,
            'planosVigentes' => $planosVigentes,
        ]);
    }

    public function store(
        StoreContratacaoRequest $request,
        User $cliente,
        ContratarPlanoManualmente $contratarPlano,
    ): RedirectResponse {
        $dados = $request->validated();
        $plano = Plano::query()->findOrFail($dados['plano_id']);

        $contratarPlano->executar(
            cliente: $cliente,
            plano: $plano,
            inicioEm: CarbonImmutable::parse($dados['inicio_em']),
            duracaoDias: (int) $dados['duracao_dias'],
            valorPago: (string) $dados['valor_pago'],
            dataPagamento: CarbonImmutable::parse($dados['data_pagamento']),
            formaPagamento: $dados['forma_pagamento'],
            observacao: $dados['observacao'] ?? null,
            responsavel: $request->user(),
        );

        return redirect()
            ->route('admin.clientes.show', $cliente)
            ->with('sucesso', 'Plano contratado e pagamento registrado com sucesso.');
    }
}
