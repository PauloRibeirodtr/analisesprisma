<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChillbetWheelHistoryRequest;
use App\Services\ChillbetWheelService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ChillbetWheelController extends Controller
{
    public function __invoke(
        ChillbetWheelHistoryRequest $request,
        ChillbetWheelService $chillbetWheelService,
    ): JsonResponse {
        $limit = (int) ($request->validated('limit') ?? config('chillbet.default_limit', 300));

        try {
            $history = $chillbetWheelService->latest($limit);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
                'total' => 0,
                'resultados' => [],
            ], 502);
        }

        return response()->json([
            'tipo' => 'chillbet-wheel-historico',
            'limit' => $limit,
            ...$history,
        ]);
    }
}
