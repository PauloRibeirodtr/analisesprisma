<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFour();

        Gate::define(
            'administrar-clientes',
            fn (User $usuario): bool => in_array(
                $usuario->perfil,
                User::PERFIS_ACESSO_IRRESTRITO,
                true,
            ),
        );

        Gate::define(
            'administrar-usuario',
            fn (User $usuario, User $alvo): bool => $usuario->perfil === 'administrador'
                || (
                    $usuario->perfil === 'analista'
                    && $alvo->perfil === 'cliente'
                ),
        );

        Gate::define(
            'administrar-perfis-administrativos',
            fn (User $usuario): bool => $usuario->perfil === 'administrador',
        );

        Gate::define(
            'administrar-planos',
            fn (User $usuario): bool => $usuario->perfil === 'administrador',
        );
    }
}
