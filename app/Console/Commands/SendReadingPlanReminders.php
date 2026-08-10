<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendReadingPlanReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-reading-plan-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画のリマインダー通知を送信する';

    /**
     * Execute the console command.
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
                $readingPlan->user->notify(
                    new ReadingPlanReminderNotification($readingPlan, 'three_days_before')
                );
                $readingPlan->update([
                    'three_days_before_notified_at' => now(),
                ]);
            }

            if ($readingPlan->status === ReadingPlanStatus::InProgress
                && $targetDate->isSameDay($today)
                && $readingPlan->on_due_date_notified_at === null) {
                $readingPlan->user->notify(
                    new ReadingPlanReminderNotification($readingPlan, 'on_due_date')
                );
                $readingPlan->update([
                    'on_due_date_notified_at' => now(),
                ]);
            }

            if ($readingPlan->status === ReadingPlanStatus::Overdue
                && $targetDate->isSameDay($today->copy()->subDays(3))
                && $readingPlan->three_days_after_notified_at === null) {
                $readingPlan->user->notify(
                    new ReadingPlanReminderNotification($readingPlan, 'three_days_after')
                );
                $readingPlan->update([
                    'three_days_after_notified_at' => now(),
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
