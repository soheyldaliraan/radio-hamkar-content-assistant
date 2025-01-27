<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('news:fetch')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/schedule-news-fetch.log'));

Schedule::command('news:regenerate --all')
            ->twiceDaily(12, 23) 
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/schedule-news-regenerate.log'));
