<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ChillbetWheelSocketTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ChillbetWheelSocketTokenController extends Controller
{
    public function __invoke(
        Request $request,
        ChillbetWheelSocketTokenService $tokenService,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        try {
            $tokenData = $tokenService->generate($user);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'ws_url' => config('chillbet.ws_url'),
            ...$tokenData,
        ]);
    }
}
