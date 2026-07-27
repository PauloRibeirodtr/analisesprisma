<?php

namespace App\Http\Controllers;

use App\Services\PainelJanelasDoubleBrancoService;
use Illuminate\Http\JsonResponse;

class JanelaPagamentoDoubleBrancoController extends Controller
{
    public function __invoke(
        PainelJanelasDoubleBrancoService $painel,
    ): JsonResponse {
        return response()->json($painel->montar());
    }
}
