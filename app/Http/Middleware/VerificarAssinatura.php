<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarAssinatura
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        if ($request->routeIs('assinatura.indisponivel')) {
            return $next($request);
        }

        $usuario = $request->user();

        if (! $usuario instanceof User) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Não autenticado.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            return redirect()->guest(route('login'));
        }

        if ($usuario->possuiPlanoVigente($slug)) {
            return $next($request);
        }

        $mensagem = 'Seu acesso a este módulo não está disponível ou o período contratado terminou.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $mensagem,
            ], Response::HTTP_FORBIDDEN);
        }

        return redirect()
            ->route('assinatura.indisponivel')
            ->with('modulo_solicitado', $slug);
    }
}
