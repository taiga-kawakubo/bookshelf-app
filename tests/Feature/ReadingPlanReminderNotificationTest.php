<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReadingPlanReminderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_期日3日前の通知データを生成できる(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '期日３日前の書籍',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $notification = new ReadingPlanReminderNotification(
            $readingPlan,
            'three_days_before',
        );

        $data = $notification->toDatabase($user);

        $this->assertSame($readingPlan->id, $data['reading_plan_id']);
        $this->assertSame($book->id, $data['book_id']);
        $this->assertSame('期日３日前の書籍', $data['book_title']);
        $this->assertSame(
            $readingPlan->target_date->format('Y-m-d'),
            $data['target_date']
        );
        $this->assertSame('three_days_before', $data['timing']);
        $this->assertSame(
            '読書計画の期日が近づいています',
            $data['title']
        );
        $this->assertSame(
            '「期日３日前の書籍」の期日は３日後です。',
            $data['body']
        );
    }

    public function test_期日当日の通知データを生成できる(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '期日当日の書籍',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $notification = new ReadingPlanReminderNotification(
            $readingPlan,
            'on_due_date',
        );

        $data = $notification->toDatabase($user);

        $this->assertSame($readingPlan->id, $data['reading_plan_id']);
        $this->assertSame($book->id, $data['book_id']);
        $this->assertSame('期日当日の書籍', $data['book_title']);
        $this->assertSame(
            $readingPlan->target_date->format('Y-m-d'),
            $data['target_date']
        );
        $this->assertSame('on_due_date', $data['timing']);
        $this->assertSame(
            '読書計画の期日当日です',
            $data['title']
        );
        $this->assertSame(
            '「期日当日の書籍」の期日は今日です。',
            $data['body']
        );
    }

    public function test_期日3日後の通知データを生成できる(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '期日３日後の書籍',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDays(3)->toDateString(),
            'status' => ReadingPlanStatus::Overdue,
        ]);

        $notification = new ReadingPlanReminderNotification(
            $readingPlan,
            'three_days_after',
        );

        $data = $notification->toDatabase($user);

        $this->assertSame($readingPlan->id, $data['reading_plan_id']);
        $this->assertSame($book->id, $data['book_id']);
        $this->assertSame('期日３日後の書籍', $data['book_title']);
        $this->assertSame(
            $readingPlan->target_date->format('Y-m-d'),
            $data['target_date']
        );
        $this->assertSame('three_days_after', $data['timing']);
        $this->assertSame(
            '読書計画の期日を過ぎています',
            $data['title']
        );
        $this->assertSame(
            '「期日３日後の書籍」の期日を３日過ぎています。',
            $data['body']
        );
    }

    public function test_期日3日前の書籍に通知が送られる(): void
    {
        Notification::fake();

        $this->travelTo(today());

        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('app:send-reading-plan-reminders')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $user,
            ReadingPlanReminderNotification::class,
            function (
                ReadingPlanReminderNotification $notification,
                array $channels
            ) use ($user): bool {
                $data = $notification->toDatabase($user);

                return $channels === ['database']
                    && $data['timing'] === 'three_days_before';
            }
        );

        $readingPlan->refresh();

        $this->assertNotNull(
            $readingPlan->three_days_before_notified_at
        );

        $this->travelBack();
    }

    public function test_期日当日の書籍に通知が送られる(): void
    {
        Notification::fake();

        $this->travelTo(today());

        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('app:send-reading-plan-reminders')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $user,
            ReadingPlanReminderNotification::class,
            function (
                ReadingPlanReminderNotification $notification,
                array $channels
            ) use ($user): bool {
                $data = $notification->toDatabase($user);

                return $channels === ['database']
                    && $data['timing'] === 'on_due_date';
            }
        );

        $readingPlan->refresh();

        $this->assertNotNull(
            $readingPlan->on_due_date_notified_at
        );

        $this->travelBack();
    }

    public function test_期日3日後の書籍に通知が送られる(): void
    {
        Notification::fake();

        $this->travelTo(today());

        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDays(3)->toDateString(),
            'status' => ReadingPlanStatus::Overdue,
        ]);

        $this->artisan('app:send-reading-plan-reminders')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $user,
            ReadingPlanReminderNotification::class,
            function (
                ReadingPlanReminderNotification $notification,
                array $channels
            ) use ($user): bool {
                $data = $notification->toDatabase($user);

                return $channels === ['database']
                    && $data['timing'] === 'three_days_after';
            }
        );

        $readingPlan->refresh();

        $this->assertNotNull(
            $readingPlan->three_days_after_notified_at
        );

        $this->travelBack();
    }

    public function test_対象ユーザーにだけ通知される(): void
    {
        Notification::fake();

        $this->travelTo(today());

        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $targetBook = Book::factory()->create();
        $otherBook = Book::factory()->create();

        ReadingPlan::create([
            'user_id' => $targetUser->id,
            'book_id' => $targetBook->id,
            'target_date' => today()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'target_date' => today()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::Completed,
        ]);

        $this->artisan('app:send-reading-plan-reminders')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $targetUser,
            ReadingPlanReminderNotification::class
        );

        Notification::assertNotSentTo(
            $otherUser,
            ReadingPlanReminderNotification::class
        );

        $this->travelBack();
    }

    public function test_通知済みの場合は再通知されない(): void
    {
        Notification::fake();

        $this->travelTo(today());

        $user = User::factory()->create();
        $book = Book::factory()->create();

        $notifiedAt = now();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
            'three_days_before_notified_at' => $notifiedAt,
        ]);

        $this->artisan('app:send-reading-plan-reminders')
            ->assertExitCode(0);

        Notification::assertNothingSent();

        $readingPlan->refresh();

        $this->assertSame(
            $notifiedAt->toDateTimeString(),
            $readingPlan->three_days_before_notified_at->toDateTimeString()
        );

        $this->travelBack();
    }
}
