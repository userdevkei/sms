<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
    Schedule::command('communications:process')->everyFiveMinutes()->withoutOverlapping();
})->purpose('Display an inspiring quote');

