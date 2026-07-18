<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanoRequest;
use App\Http\Requests\Admin\UpdatePlanoRequest;
use App\Models\Plano;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanoController extends Controller
{
    public function index(Request $request): View
    {
        $busca = $request->string('busca')->trim()->toString();
        $situacao = $request->string('situacao')->toString();
        $situacao = in_array(
            $situacao,
            ['todos', 'venda', 'indisponiveis', 'arquivados'],
            true,
        ) ? $situacao : 'todos';

        $planos = Plano::query()
            ->withTrashed()
            ->withCount('assinaturas')
            ->when($busca !== '', fn (Builder $query) => $query->where(
                fn (Builder $filtro) => $filtro
                    ->where('nome', 'like', "%{$busca}%")
                    ->orWhere('slug', 'like', "%{$busca}%")
                    ->orWhere('descricao', 'like', "%{$busca}%"),
            ))
            ->when(
                $situacao === 'venda',
                fn (Builder $query) => $query
                    ->whereNull('deleted_at')
                    ->where('disponivel_para_venda', true),
            )
            ->when(
                $situacao === 'indisponiveis',
                fn (Builder $query) => $query
                    ->whereNull('deleted_at')
                    ->where('disponivel_para_venda', false),
            )
            ->when(
                $situacao === 'arquivados',
                fn (Builder $query) => $query->onlyTrashed(),
            )
            ->orderBy('deleted_at')
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('admin.planos.index', compact(
            'busca',
            'planos',
            'situacao',
        ));
    }

    public function create(): View
    {
        return view('admin.planos.create');
    }

    public function store(StorePlanoRequest $request): RedirectResponse
    {
        $plano = Plano::query()->create($request->validated());

        return redirect()
            ->route('admin.planos.edit', $plano)
            ->with('sucesso', 'Plano cadastrado com sucesso.');
    }

    public function edit(Plano $plano): View
    {
        abort_if($plano->ePlanoSistema(), 403);

        return view('admin.planos.edit', compact('plano'));
    }

    public function update(
        UpdatePlanoRequest $request,
        Plano $plano,
    ): RedirectResponse {
        $plano->update($request->validated());

        return redirect()
            ->route('admin.planos.edit', $plano)
            ->with('sucesso', 'Plano atualizado com sucesso.');
    }

    public function destroy(Plano $plano): RedirectResponse
    {
        abort_if($plano->ePlanoSistema(), 403);

        $plano->delete();

        return redirect()
            ->route('admin.planos.index')
            ->with('sucesso', 'Plano arquivado sem apagar seu histórico.');
    }

    public function restore(Plano $plano): RedirectResponse
    {
        abort_unless($plano->trashed(), 404);

        $plano->restore();

        return redirect()
            ->route('admin.planos.index')
            ->with('sucesso', 'Plano restaurado com sucesso.');
    }
}
