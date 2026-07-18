<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Usuarios\CriarUsuarioAdministrativamente;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClienteRequest;
use App\Http\Requests\Admin\UpdateClienteRequest;
use App\Models\Assinatura;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $busca = $request->string('busca')->trim()->toString();
        $clientes = User::query()
            ->select([
                'id',
                'name',
                'email',
                'cpf_cnpj',
                'telefone',
                'perfil',
                'created_at',
            ])
            ->where('perfil', 'cliente')
            ->when($busca !== '', fn (Builder $query) => $query->where(
                fn (Builder $filtro) => $filtro
                    ->where('name', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%")
                    ->orWhere('cpf_cnpj', 'like', "%{$busca}%")
                    ->orWhere('telefone', 'like', "%{$busca}%"),
            ))
            ->withCount([
                'assinaturas as assinaturas_vigentes_count' => fn (Builder $query) => $query->vigentes(),
            ])
            ->addSelect([
                'proximo_vencimento' => Assinatura::query()
                    ->select('termino_em')
                    ->whereColumn('user_id', 'users.id')
                    ->vigentes()
                    ->oldest('termino_em')
                    ->limit(1),
            ])
            ->withCasts(['proximo_vencimento' => 'datetime'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.clientes.index', compact('busca', 'clientes'));
    }

    public function create(Request $request): View
    {
        return view('admin.clientes.create', [
            'podeAdministrarPerfis' => $request->user()
                ->can('administrar-perfis-administrativos'),
        ]);
    }

    public function store(
        StoreClienteRequest $request,
        CriarUsuarioAdministrativamente $criarUsuario,
    ): RedirectResponse {
        $usuario = $criarUsuario->executar($request->validated());

        return redirect()
            ->route('admin.clientes.show', $usuario)
            ->with('sucesso', 'Usuário cadastrado com sucesso.');
    }

    public function show(User $cliente): View
    {
        Gate::authorize('administrar-usuario', $cliente);

        $assinaturas = $cliente->assinaturas()
            ->with('plano')
            ->latest('inicio_em')
            ->paginate(10, ['*'], 'assinaturas')
            ->withQueryString();
        $pagamentos = $cliente->pagamentos()
            ->with([
                'assinatura:id,nome_plano',
                'registradoPor:id,name',
            ])
            ->latest('data_pagamento')
            ->paginate(10, ['*'], 'pagamentos')
            ->withQueryString();

        return view(
            'admin.clientes.show',
            compact('assinaturas', 'cliente', 'pagamentos'),
        );
    }

    public function edit(Request $request, User $cliente): View
    {
        Gate::authorize('administrar-usuario', $cliente);

        return view('admin.clientes.edit', [
            'cliente' => $cliente,
            'podeAdministrarPerfis' => $request->user()
                ->can('administrar-perfis-administrativos'),
        ]);
    }

    public function update(
        UpdateClienteRequest $request,
        User $cliente,
    ): RedirectResponse {
        $cliente->update($request->validated());

        return redirect()
            ->route('admin.clientes.show', $cliente)
            ->with('sucesso', 'Dados do usuário atualizados com sucesso.');
    }
}
