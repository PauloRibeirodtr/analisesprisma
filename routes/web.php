<?php

use App\Http\Controllers\ChillbetWheelController;
use App\Http\Controllers\ChillbetWheelSocketTokenController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

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
