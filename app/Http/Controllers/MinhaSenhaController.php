<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtualizarMinhaSenhaRequest;
use Illuminate\Http\RedirectResponse;

class MinhaSenhaController extends Controller
{
    public function __invoke(
        AtualizarMinhaSenhaRequest $request,
    ): RedirectResponse {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return back()->with(
            'sucesso_senha',
            'Sua senha foi atualizada com segurança.',
        );
    }
}
