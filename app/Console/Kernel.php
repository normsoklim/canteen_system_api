<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ProcessExpiredBakongPayments;
use App\Console\Commands\DailyReportCommand;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run the expired Bakong payments command every 5 minutes
        $schedule->command(ProcessExpiredBakongPayments::class)->everyFiveMinutes();
        // Run the daily report command daily
        $schedule->command('report:daily')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        
        require base_path('routes/console.php');
    }
}