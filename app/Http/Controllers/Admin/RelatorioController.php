<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRelatorioRequest;
use App\Queries\Admin\RelatorioAdministrativo;
use Illuminate\View\View;

class RelatorioController extends Controller
{
    public function index(
        IndexRelatorioRequest $request,
        RelatorioAdministrativo $relatorioAdministrativo,
    ): View {
        $dados = $relatorioAdministrativo->consultar($request->validated());

        return view('admin.relatorios.index', [
            ...$dados,
            ...$relatorioAdministrativo->opcoesDosFiltros(),
            'relatorios' => RelatorioAdministrativo::RELATORIOS,
        ]);
    }
}
