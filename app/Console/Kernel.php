<?php

namespace App\Console;

use App\Jobs\SitemapGenerator;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('sanctum:prune-expired --hours=24')->daily();
        $schedule->command('command:featureHourlyProfit')->everyMinute();
        $schedule->command('command:prune-unverified')->daily();
        $schedule->command('command:release-feature')->daily();
        $schedule->command('command:activate-feature-hourly-profit')->daily();
        $schedule->job(new SitemapGenerator)->everyThreeHours();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
