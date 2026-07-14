<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pulse scheduled tasks
Schedule::command('pulse:trending')->hourly();
Schedule::command('pulse:badges')->dailyAt('03:00');
Schedule::command('queue:work --stop-when-empty')->everyFiveMinutes()->withoutOverlapping();
