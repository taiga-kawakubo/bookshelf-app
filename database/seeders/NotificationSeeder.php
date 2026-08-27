<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class NotificationSeeder extends Seeder
{
    /**
     * 通知一覧確認用の通知データを作成する。
     */
    public function run(): void
    {
        $now = Carbon::now();
        $yamada = User::where('email', 'yamada@example.com')->firstOrFail();
        $books = Book::orderBy('id')->take(11)->get()->values();

        $notifications = [
            [
                'book_id' => $books[1]->id,
                'timing' => 'three_days_before',
                'notified_at' => $now->copy(),
                'read_at' => null,
            ],
            [
                'book_id' => $books[2]->id,
                'timing' => 'three_days_before',
                'notified_at' => $now->copy(),
                'read_at' => $now->copy(),
            ],
            [
                'book_id' => $books[3]->id,
                'timing' => 'on_due_date',
                'notified_at' => $now->copy(),
                'read_at' => null,
            ],
            [
                'book_id' => $books[4]->id,
                'timing' => 'on_due_date',
                'notified_at' => $now->copy(),
                'read_at' => $now->copy(),
            ],
            [
                'book_id' => $books[5]->id,
                'timing' => 'three_days_after',
                'notified_at' => $now->copy(),
                'read_at' => $now->copy(),
            ],
            [
                'book_id' => $books[6]->id,
                'timing' => 'three_days_after',
                'notified_at' => $now->copy(),
                'read_at' => null,
            ],
            [
                'book_id' => $books[7]->id,
                'timing' => 'three_days_after',
                'notified_at' => $now->copy()->subDays(4),
                'read_at' => $now->copy()->subDays(4),
            ],
        ];

        collect($notifications)->each(function (array $notificationData) use ($yamada): void {
            $readingPlan = ReadingPlan::with('book')
                ->where('user_id', $yamada->id)
                ->where('book_id', $notificationData['book_id'])
                ->firstOrFail();

            $this->createNotification(
                $yamada,
                $readingPlan,
                $notificationData['timing'],
                $notificationData['notified_at'],
                $notificationData['read_at']
            );
        });
    }

    private function createNotification(
        User $user,
        ReadingPlan $readingPlan,
        string $timing,
        Carbon $notifiedAt,
        ?Carbon $readAt
    ): void {
        $reminder = new ReadingPlanReminderNotification($readingPlan, $timing);
        $notification = $this->findNotification($user, $readingPlan, $timing);

        if ($notification === null) {
            Notification::send($user, $reminder);

            $notification = $this->findNotification(
                $user,
                $readingPlan,
                $timing
            );
        }

        if ($notification !== null) {
            $notification->forceFill([
                'data' => $reminder->toDatabase($user),
                'read_at' => $readAt,
                'created_at' => $notifiedAt,
                'updated_at' => $readAt ?? $notifiedAt,
            ])->save();
        }

        $readingPlan->update([
            $this->notifiedAtColumn($timing) => $notifiedAt,
        ]);
    }

    private function findNotification(
        User $user,
        ReadingPlan $readingPlan,
        string $timing
    ): ?DatabaseNotification {
        return $user->notifications()
            ->get()
            ->first(function (DatabaseNotification $notification) use ($readingPlan, $timing): bool {
                return (int) ($notification->data['reading_plan_id'] ?? 0) === $readingPlan->id
                    && ($notification->data['timing'] ?? null) === $timing;
            });
    }

    private function notifiedAtColumn(string $timing): string
    {
        return match ($timing) {
            'three_days_before' => 'three_days_before_notified_at',
            'on_due_date' => 'on_due_date_notified_at',
            'three_days_after' => 'three_days_after_notified_at',
        };
    }
}
