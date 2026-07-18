<?php

namespace App\Http\Controllers;

use App\Models\Plano;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcessoIndisponivelController extends Controller
{
    public function __invoke(Request $request): View
    {
        $nomeModulo = null;
        $ultimoVencimento = null;
        $slug = $request->session()->get('modulo_solicitado');
        $usuario = $request->user();

        if (is_string($slug) && $slug !== '' && $usuario instanceof User) {
            $plano = Plano::withTrashed()
                ->where('slug', $slug)
                ->first(['id', 'nome', 'slug']);
            $assinaturaRecente = $usuario->assinaturas()
                ->select([
                    'id',
                    'plano_id',
                    'nome_plano',
                    'termino_em',
                ])
                ->with('plano:id,slug')
                ->whereHas(
                    'plano',
                    fn ($query) => $query->whereIn('slug', [
                        $slug,
                        Plano::SLUG_TESTE,
                    ]),
                )
                ->latest('termino_em')
                ->first();

            $nomeModulo = $plano?->nome;
            $ultimoVencimento = $assinaturaRecente?->termino_em;

            if (
                $nomeModulo === null
                && $assinaturaRecente?->plano?->slug === $slug
            ) {
                $nomeModulo = $assinaturaRecente->nome_plano;
            }
        }

        return view('assinatura-indisponivel', compact(
            'nomeModulo',
            'ultimoVencimento',
        ));
    }
}
