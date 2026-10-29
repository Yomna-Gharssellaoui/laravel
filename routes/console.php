<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily: expire certificates and raise automatic alerts (run `php artisan schedule:work` locally)
Schedule::command('traceverte:scan-alerts')->dailyAt('06:00');
