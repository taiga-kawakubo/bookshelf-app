<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証ユーザーが通知一覧を表示できる(): void
    {
        $user = User::factory()->create();
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ReadingPlanReminderNotification::class,
            'data' => [
                'title' => '通知タイトル',
                'body' => '通知本文',
                'timing' => 'on_due_date',
            ],
            'read_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewIs('notifications.index');
        $response->assertSeeText('通知タイトル');
    }

    public function test_自分の通知だけ表示される(): void
    {

        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $targetUser->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ReadingPlanReminderNotification::class,
            'data' => [
                'title' => '通知1タイトル',
                'body' => '通知1本文',
                'timing' => 'on_due_date',
            ],
            'read_at' => null,
        ]);

        $otherUser->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ReadingPlanReminderNotification::class,
            'data' => [
                'title' => '通知2タイトル',
                'body' => '通知2本文',
                'timing' => 'on_due_date',
            ],
            'read_at' => null,
        ]);

        $response = $this
            ->actingAs($targetUser)
            ->get(route('notifications.index'));

        $response->assertOk();

        $response->assertSeeText('通知1タイトル');
        $response->assertDontSeeText('通知2タイトル');
    }

    public function test_自分の通知を既読にできる(): void
    {
        $user = User::factory()->create();

        $notification1 = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ReadingPlanReminderNotification::class,
            'data' => [
                'title' => '通知1タイトル',
                'body' => '通知1本文',
                'timing' => 'on_due_date',
            ],
            'read_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('notifications.read', [
                'notification' => $notification1,
            ]));

        $response->assertRedirect(route('notifications.index'));
        $response->assertSessionHas(
            'success',
            '通知を既読にしました。'
        );

        $notification1->refresh();

        $this->assertNotNull($notification1->read_at);
    }

    public function test_他のユーザーの通知は既読にできない(): void
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $targetUser->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ReadingPlanReminderNotification::class,
            'data' => [
                'title' => '通知1タイトル',
                'body' => '通知1本文',
                'timing' => 'on_due_date',
            ],
            'read_at' => null,
        ]);

        $notification2 = $otherUser->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ReadingPlanReminderNotification::class,
            'data' => [
                'title' => '通知2タイトル',
                'body' => '通知2本文',
                'timing' => 'on_due_date',
            ],
            'read_at' => null,
        ]);

        $response = $this
            ->actingAs($targetUser)
            ->post(route('notifications.read', [
                'notification' => $notification2,
            ]));

        $response->assertForbidden();

        $notification2->refresh();

        $this->assertNull($notification2->read_at);
    }

    public function test_未認証ユーザーは通知画面へアクセスできない(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }
}
