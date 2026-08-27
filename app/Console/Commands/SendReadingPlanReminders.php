<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

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
     *
     * @param  ReadingPlan  $readingPlan  通知対象の読書計画
     * @param  string  $timing  通知タイミング
     * @param  string  $notifiedAtColumn  通知済み日時を保存するカラム名
     */
    private function sendReminder(ReadingPlan $readingPlan, string $timing, string $notifiedAtColumn): void
    {
        DB::transaction(function () use ($readingPlan, $timing, $notifiedAtColumn): void {
            Notification::send(
                $readingPlan->user,
                new ReadingPlanReminderNotification($readingPlan, $timing)
            );

            $readingPlan->update([
                $notifiedAtColumn => now(),
            ]);
        });
    }

    /**
     * 読書計画の期日に応じてリマインダー通知を送信する
     *
     * @return int コマンドの終了ステータス
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

        $readingPlans
            ->each(function ($readingPlan) use ($today): void {
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
            });

        return Command::SUCCESS;
    }
}
