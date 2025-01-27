<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Inspire command (for testing)
        $schedule->command('inspire')
            ->hourly();

        // Fetch news articles every hour
        $schedule->command('news:fetch')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/schedule-news-fetch.log'));

        // Regenerate content twice daily
        $schedule->command('news:regenerate --all')
            ->twiceDaily(12, 23)
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/schedule-news-regenerate.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 