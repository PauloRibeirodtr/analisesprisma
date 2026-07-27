<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('double:sincronizar-janelas')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('double:apurar-janelas')
    ->everyTenSeconds()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();
