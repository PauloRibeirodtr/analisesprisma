<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPerfil
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$perfisPermitidos,
    ): Response {
        $usuario = $request->user();

        if (! $usuario instanceof User) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Não autenticado.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            return redirect()->guest(route('login'));
        }

        if (in_array($usuario->perfil, $perfisPermitidos, true)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Você não possui permissão para acessar esta área.',
            ], Response::HTTP_FORBIDDEN);
        }

        abort(
            Response::HTTP_FORBIDDEN,
            'Você não possui permissão para acessar esta área.',
        );
    }
}
