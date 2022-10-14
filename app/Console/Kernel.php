<?php

namespace App\Console;

use App\Console\Commands\Callback;
use App\Console\Commands\DeleteUnregisteredSending;
use App\Console\Commands\Expiration;
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
        // $schedule->command('inspire')->hourly();
        $schedule->command(DeleteUnregisteredSending::class)
            ->weekly();
//        $schedule->command(Callback::class)
//            ->dailyAt('08:00');
//        $schedule->command(Expiration::class)
//            ->dailyAt('01:00');
        $schedule->command(Callback::class)
            ->everyMinute();
        $schedule->command(Expiration::class)
            ->everyMinute();

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
