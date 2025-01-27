<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('schedule:test', function () {
    $message = 'Schedule test running at: ' . now()->format('Y-m-d H:i:s');
    $this->comment($message);
    Log::info($message);
})->purpose('Test scheduler is working');

// Schedule the test command to run every minute
Schedule::command('schedule:test')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/schedule-test.log'));

// Main application schedules
Schedule::command('news:fetch')
    ->cron('0 */2 * * *')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule-news-fetch.log'));

Schedule::command('news:regenerate --all')
    ->cron('0 12,23 * * *')
    ->appendOutputTo(storage_path('logs/schedule-news-regenerate.log'));
