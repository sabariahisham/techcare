<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
   protected function schedule(Schedule $schedule)
{
    // Run on 31 Dec at 11pm — scrapes next year's holidays before the year starts
    // $schedule->command('scrape:holidays ' . (date('Y') + 1))
    //     ->yearlyOn(12, 31, '23:00');
}

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
