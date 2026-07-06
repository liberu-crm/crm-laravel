<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('social-media:publish-scheduled')->everyMinute();
Schedule::command('social-media:update-analytics')->hourly();
Schedule::command('tasks:notify-overdue')->dailyAt('07:00');
