<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendReadingPlanReminders extends Command
{
    /**
     * コマンド名とシグネチャ
     *
     * @var string
     */
    protected $signature = 'app:send-reading-plan-reminders';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = '読書計画のリマインダー通知を送信する';

    /**
     * 通知を送信し、通知済み日時を保存する
     */
    private function sendReminder(ReadingPlan $readingPlan, string $timing, string $notifiedAtColumn): void
    {
        DB::transaction(function () use ($readingPlan, $timing, $notifiedAtColumn): void {
            $readingPlan->user->notify(
                new ReadingPlanReminderNotification($readingPlan, $timing)
            );

            $readingPlan->update([
                $notifiedAtColumn => now(),
            ]);
        });
    }

    /**
     * コマンドを実行する
     */
    public function handle(): int
    {
        $today = Carbon::today();
        $readingPlans = ReadingPlan::with(['book', 'user'])
            ->whereIn('status', [
                ReadingPlanStatus::InProgress->value,
                ReadingPlanStatus::Overdue->value,
            ])
            ->get();

        foreach ($readingPlans as $readingPlan) {
            $targetDate = $readingPlan->target_date;

            if ($readingPlan->status === ReadingPlanStatus::InProgress
                && $targetDate->isSameDay($today->copy()->addDays(3))
                && $readingPlan->three_days_before_notified_at === null) {
                $this->sendReminder(
                    $readingPlan,
                    'three_days_before',
                    'three_days_before_notified_at'
                );
            }

            if ($readingPlan->status === ReadingPlanStatus::InProgress
                && $targetDate->isSameDay($today)
                && $readingPlan->on_due_date_notified_at === null) {
                $this->sendReminder(
                    $readingPlan,
                    'on_due_date',
                    'on_due_date_notified_at'
                );
            }

            if ($readingPlan->status === ReadingPlanStatus::Overdue
                && $targetDate->isSameDay($today->copy()->subDays(3))
                && $readingPlan->three_days_after_notified_at === null) {
                $this->sendReminder(
                    $readingPlan,
                    'three_days_after',
                    'three_days_after_notified_at'
                );
            }
        }

        return Command::SUCCESS;
    }
}
