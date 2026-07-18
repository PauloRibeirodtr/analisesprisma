<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MinhaAssinaturaController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $usuario */
        $usuario = $request->user();

        $assinaturasVigentes = $usuario->assinaturas()
            ->with('plano')
            ->vigentes()
            ->orderBy('termino_em')
            ->get();

        $assinaturas = $usuario->assinaturas()
            ->with('plano')
            ->latest('inicio_em')
            ->paginate(10)
            ->withQueryString();

        return view('minha-assinatura.index', [
            'acessoAdministrativo' => in_array(
                $usuario->perfil,
                User::PERFIS_ACESSO_IRRESTRITO,
                true,
            ),
            'assinaturas' => $assinaturas,
            'assinaturasVigentes' => $assinaturasVigentes,
            'proximoVencimento' => $assinaturasVigentes->first()?->termino_em,
            'usuario' => $usuario,
        ]);
    }
}
