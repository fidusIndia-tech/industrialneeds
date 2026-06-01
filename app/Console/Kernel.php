<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // ---- Background product-image pipeline (hands-off) ----
        // 1) Once a day, enqueue up to ~a day's DigiKey quota worth of products that still need an
        //    image. Only 'placeholder' products are picked (in-flight 'queued' jobs are not re-sent);
        //    quota-blocked products reset themselves to 'placeholder' and are retried the next day.
        $schedule->command('products:dispatch-image-jobs --limit=1000 --include-failed')
            ->dailyAt('01:00')
            ->withoutOverlapping(30)
            ->runInBackground();

        // 2) Frequently drain the 'images' queue with a short-lived worker (no permanent daemon
        //    needed). --stop-when-empty exits when done; --max-time keeps each run under a minute so
        //    the next tick starts fresh; withoutOverlapping prevents pile-ups.
        $schedule->command('queue:work database --queue=images --tries=3 --stop-when-empty --max-time=55 --sleep=1')
            ->everyMinute()
            ->withoutOverlapping(10)
            ->runInBackground();
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
