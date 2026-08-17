<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * アプリケーションのコマンドスケジュールを定義
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:update-overdue-reading-plans')->dailyAt('0:00')->timezone('Asia/Tokyo');
        $schedule->command('app:send-reading-plan-reminders')->dailyAt('8:00')->timezone('Asia/Tokyo');
    }

    /**
     * アプリケーションのコマンドを登録
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
