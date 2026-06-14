<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Release expired booking holds every minute so abandoned slots become bookable again.
Schedule::command('bookings:expire-holds')->everyMinute()->withoutOverlapping();
