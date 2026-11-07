<?php

use App\Console\ImportSchedule;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automatischer Wikipedia-Import (läuft über `php artisan schedule:work` bzw. `schedule:run`)
ImportSchedule::register(app(Schedule::class));
