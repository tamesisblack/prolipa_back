<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Console\Scheduling\Event;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \Laravelista\LumenVendorPublish\VendorPublishCommand::class,
        Commands\DailyQuote::class,
        Commands\AgendaCron::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // $schedule->command('quote:daily')->everyFiveMinutes();

        // $schedule->call('App\Http\Controllers\MatriculaController@enviar_recordatorio')->daily();

        $schedule->call('App\Http\Controllers\MatriculaController@agenda_asesores')->everyFiveMinutes();

        $schedule->command('agenda:cron')->everyFiveMinutes();

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
