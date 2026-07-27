<?php

use App\Http\Controllers\AcessoIndisponivelController;
use App\Http\Controllers\Admin\ClienteAssinaturaController;
use App\Http\Controllers\Admin\ClienteAssinaturaReativacaoController;
use App\Http\Controllers\Admin\ClienteAssinaturaRenovacaoController;
use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\PlanoController;
use App\Http\Controllers\Admin\RelatorioController;
use App\Http\Controllers\ChillbetWheelController;
use App\Http\Controllers\ChillbetWheelSocketTokenController;
use App\Http\Controllers\DoubleSocketTokenController;
use App\Http\Controllers\JanelaPagamentoDoubleBrancoController;
use App\Http\Controllers\MinhaAssinaturaController;
use App\Http\Controllers\MinhaSenhaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/acesso-indisponivel', AcessoIndisponivelController::class)
    ->middleware('auth')
    ->name('assinatura.indisponivel');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    Route::view('/blaze/double-branco', 'blaze-double-branco')
        ->name('blaze.double-branco');

    Route::get('/api/blaze/double/socket-token', DoubleSocketTokenController::class)
        ->middleware('throttle:30,1')
        ->name('blaze.double.socket-token');

    Route::get(
        '/api/blaze/double/janelas-pagamento',
        JanelaPagamentoDoubleBrancoController::class,
    )
        ->middleware('throttle:60,1')
        ->name('blaze.double.janelas-pagamento');

    Route::get('/minha-assinatura', [
        MinhaAssinaturaController::class,
        'index',
    ])->name('minha-assinatura.index');

    Route::patch('/minha-assinatura/senha', MinhaSenhaController::class)
        ->middleware('throttle:6,1')
        ->name('minha-assinatura.senha.update');

    Route::middleware('assinatura:wheel')->group(function () {
        Route::view('/chillbet/wheel', 'chillbet-wheel')
            ->name('chillbet.wheel');

        Route::redirect('/analises', '/chillbet/wheel')
            ->name('analises.index');

        Route::get('/api/chillbet/wheel/ultimos', ChillbetWheelController::class)
            ->middleware('throttle:60,1')
            ->name('api.chillbet.wheel.latest');

        Route::get('/api/chillbet/wheel/socket-token', ChillbetWheelSocketTokenController::class)
            ->middleware('throttle:30,1')
            ->name('chillbet.wheel.socket-token');
    });
});

Route::middleware([
    'auth',
    'verified',
    'perfil:administrador,analista',
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get(
            'clientes/{cliente}/assinaturas/create',
            [ClienteAssinaturaController::class, 'create'],
        )->name('clientes.assinaturas.create');

        Route::post(
            'clientes/{cliente}/assinaturas',
            [ClienteAssinaturaController::class, 'store'],
        )->name('clientes.assinaturas.store');

        Route::scopeBindings()->group(function () {
            Route::get(
                'clientes/{cliente}/assinaturas/{assinatura}/renovar',
                [ClienteAssinaturaRenovacaoController::class, 'create'],
            )->name('clientes.assinaturas.renovacao.create');

            Route::post(
                'clientes/{cliente}/assinaturas/{assinatura}/renovar',
                [ClienteAssinaturaRenovacaoController::class, 'store'],
            )->name('clientes.assinaturas.renovacao.store');

            Route::get(
                'clientes/{cliente}/assinaturas/{assinatura}/reativar',
                [ClienteAssinaturaReativacaoController::class, 'create'],
            )->name('clientes.assinaturas.reativacao.create');

            Route::post(
                'clientes/{cliente}/assinaturas/{assinatura}/reativar',
                [ClienteAssinaturaReativacaoController::class, 'store'],
            )->name('clientes.assinaturas.reativacao.store');
        });

        Route::resource('clientes', ClienteController::class)
            ->except('destroy');

        Route::get('relatorios', [RelatorioController::class, 'index'])
            ->name('relatorios.index');

        Route::middleware('can:administrar-planos')->group(function () {
            Route::patch(
                'planos/{plano}/restaurar',
                [PlanoController::class, 'restore'],
            )
                ->withTrashed()
                ->name('planos.restore');

            Route::resource('planos', PlanoController::class)
                ->except('show');
        });
    });
