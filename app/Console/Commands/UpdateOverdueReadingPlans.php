<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateOverdueReadingPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-overdue-reading-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '期限が過ぎた読書計画を進行中から期限超過に変更';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        ReadingPlan::where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', '<', $today)
            ->update([
                'status' => ReadingPlanStatus::Overdue->value,
            ]);
    }
}
