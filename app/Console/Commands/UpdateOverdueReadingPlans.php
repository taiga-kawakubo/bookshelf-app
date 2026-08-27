<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateOverdueReadingPlans extends Command
{
    /**
     * コマンド名とシグネチャ
     *
     * @var string
     */
    protected $signature = 'app:update-overdue-reading-plans';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = '期限が過ぎた読書計画を進行中から期限超過に変更';

    /**
     * 期日を過ぎた進行中の読書計画を期限超過に更新する
     *
     * @return int コマンドの終了ステータス
     */
    public function handle(): int
    {
        $today = Carbon::today();
        ReadingPlan::where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', '<', $today)
            ->update([
                'status' => ReadingPlanStatus::Overdue->value,
            ]);

        return Command::SUCCESS;
    }
}
