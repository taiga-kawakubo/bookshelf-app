<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト用の読書計画を作成する。
     * テストごとに変更したい値は、$overrideで上書きできる。
     *
     * @param  array<string, mixed>  $override
     */
    private function createReadingPlan(User $user, Book $book, array $override = []): ReadingPlan
    {
        return ReadingPlan::create(array_merge([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDays(7)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ], $override));
    }

    public function test_認証済みユーザーは自分の読書計画だけ一覧表示できる(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownBook = Book::factory()->create([
            'title' => '自分の読書計画用書籍',
        ]);
        $otherBook = Book::factory()->create([
            'title' => '他ユーザーの読書計画用書籍',
        ]);

        $ownPlan = $this->createReadingPlan($user, $ownBook);
        $this->createReadingPlan($otherUser, $otherBook);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk();
        $response->assertViewIs('reading-plans.index');

        $readingPlans = $response->viewData('readingPlans');

        $this->assertCount(1, $readingPlans);
        $this->assertSame($ownPlan->id, $readingPlans->first()->id);
        $response->assertSeeText($ownBook->title);
        $response->assertDontSeeText($otherBook->title);
    }

    public function test_読書計画一覧をステータスで絞り込める(): void
    {
        $user = User::factory()->create();
        $inProgressBook = Book::factory()->create([
            'title' => '進行中の読書計画用書籍',
        ]);
        $completedBook = Book::factory()->create([
            'title' => '読了済みの読書計画用書籍',
        ]);

        $this->createReadingPlan($user, $inProgressBook);
        $completedPlan = $this->createReadingPlan($user, $completedBook, [
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => ReadingPlanStatus::Completed->value,
            ]));

        $response->assertOk();
        $response->assertViewIs('reading-plans.index');

        $readingPlans = $response->viewData('readingPlans');

        $this->assertSame(
            ReadingPlanStatus::Completed->value,
            $response->viewData('currentStatus')
        );
        $this->assertCount(1, $readingPlans);
        $this->assertSame($completedPlan->id, $readingPlans->first()->id);
        $response->assertSeeText($completedBook->title);
        $response->assertDontSeeText($inProgressBook->title);
    }

    public function test_認証済みユーザーは読書計画作成画面を表示できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'title' => '作成画面に表示する書籍',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.create'));

        $response->assertOk();
        $response->assertViewIs('reading-plans.create');
        $response->assertViewHas('books');
        $response->assertSeeText($book->title);
    }

    public function test_認証済みユーザーは読書計画を登録できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = today()->addDays(14)->toDateString();

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => $targetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas(
            'success',
            '読書計画を作成しました。'
        );

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);

        $readingPlan = ReadingPlan::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->firstOrFail();

        $this->assertSame(
            $targetDate,
            $readingPlan->target_date->toDateString()
        );
    }

    public function test_過去日で登録した読書計画は期限超過になる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = today()->subDay()->toDateString();

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => $targetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $readingPlan = ReadingPlan::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->firstOrFail();

        $this->assertSame(
            ReadingPlanStatus::Overdue,
            $readingPlan->status
        );

        $this->assertSame(
            $targetDate,
            $readingPlan->target_date->toDateString()
        );
    }

    public function test_今日の日付で登録した読書計画は進行中になる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = today()->toDateString();

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => $targetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $readingPlan = ReadingPlan::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->firstOrFail();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $readingPlan->status
        );

        $this->assertSame(
            $targetDate,
            $readingPlan->target_date->toDateString()
        );
    }

    public function test_未来日で登録した読書計画は進行中になる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = today()->addDay()->toDateString();

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => $targetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $readingPlan = ReadingPlan::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->firstOrFail();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $readingPlan->status
        );

        $this->assertSame(
            $targetDate,
            $readingPlan->target_date->toDateString()
        );
    }

    public function test_読書計画の所有者は編集画面を表示できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $readingPlan = $this->createReadingPlan($user, $book);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertOk();
        $response->assertViewIs('reading-plans.edit');
        $response->assertViewHas('readingPlan', $readingPlan);
        $response->assertSeeText($book->title);
    }

    public function test_読書計画の所有者は期日を更新できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $readingPlan = $this->createReadingPlan($user, $book);
        $targetDate = today()->addDays(21)->toDateString();

        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => $targetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas(
            'success',
            '読書計画を更新しました。'
        );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
        ]);

        $readingPlan->refresh();

        $this->assertSame(
            $targetDate,
            $readingPlan->target_date->toDateString()
        );
    }

    public function test_読書計画の所有者は読書計画を削除できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $readingPlan = $this->createReadingPlan($user, $book);

        $response = $this
            ->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas(
            'success',
            '読書計画を削除しました。'
        );
        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_読書計画の所有者は読了状態に変更できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $readingPlan = $this->createReadingPlan($user, $book);

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas(
            'success',
            '読書を完了しました。'
        );

        $readingPlan->refresh();

        $this->assertSame(ReadingPlanStatus::Completed, $readingPlan->status);
        $this->assertNotNull($readingPlan->completed_at);
    }

    public function test_未認証ユーザーは読書計画一覧へアクセスできない(): void
    {
        $response = $this->get(route('reading-plans.index'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_未認証ユーザーは読書計画を登録できない(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => today()->addDays(14)->toDateString(),
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_所有者でないユーザーは読書計画を更新できない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();
        $readingPlan = $this->createReadingPlan($owner, $book);

        $originalTargetDate = $readingPlan->target_date->toDateString();
        $newTargetDate = today()->addDays(21)->toDateString();

        $response = $this
            ->actingAs($otherUser)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => $newTargetDate,
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
        ]);

        $readingPlan->refresh();

        $this->assertSame(
            $originalTargetDate,
            $readingPlan->target_date->toDateString()
        );
    }

    public function test_所有者でないユーザーは読書計画を削除できない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();
        $readingPlan = $this->createReadingPlan($owner, $book);

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertForbidden();
        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_所有者でないユーザーは読書計画を読了に変更できない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();
        $readingPlan = $this->createReadingPlan($owner, $book);

        $response = $this
            ->actingAs($otherUser)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertForbidden();

        $readingPlan->refresh();

        $this->assertSame(ReadingPlanStatus::InProgress, $readingPlan->status);
        $this->assertNull($readingPlan->completed_at);
    }

    public function test_期限超過の計画を未来日に変更すると進行中に戻り通知履歴がリセットされる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Overdue,
            'three_days_before_notified_at' => now()->subDays(5),
            'on_due_date_notified_at' => now()->subDays(4),
            'three_days_after_notified_at' => now()->subDays(2),
        ]);

        $newTargetDate = today()
            ->addDays(5)
            ->toDateString();

        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => $newTargetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas(
            'success',
            '読書計画を更新しました。'
        );

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $readingPlan->status
        );

        $this->assertSame(
            $newTargetDate,
            $readingPlan->target_date->toDateString()
        );

        $this->assertNull($readingPlan->three_days_before_notified_at);
        $this->assertNull($readingPlan->on_due_date_notified_at);
        $this->assertNull($readingPlan->three_days_after_notified_at);
    }

    public function test_進行中の計画を過去日に変更すると期限超過になる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $newTargetDate = today()
            ->subDay()
            ->toDateString();

        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => $newTargetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Overdue,
            $readingPlan->status
        );

        $this->assertSame(
            $newTargetDate,
            $readingPlan->target_date->toDateString()
        );
    }

    public function test_期日が変わらない場合は通知履歴をリセットしない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $beforeNotifiedAt = now()->subDays(3);
        $onDueNotifiedAt = now()->subDay();
        $afterNotifiedAt = now()->subHours(6);

        $targetDate = today()->addDays(5);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => ReadingPlanStatus::InProgress,
            'three_days_before_notified_at' => $beforeNotifiedAt,
            'on_due_date_notified_at' => $onDueNotifiedAt,
            'three_days_after_notified_at' => $afterNotifiedAt,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => $targetDate->toDateString(),
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $readingPlan->refresh();

        $this->assertSame(
            $beforeNotifiedAt->toDateTimeString(),
            $readingPlan->three_days_before_notified_at->toDateTimeString()
        );

        $this->assertSame(
            $onDueNotifiedAt->toDateTimeString(),
            $readingPlan->on_due_date_notified_at->toDateTimeString()
        );

        $this->assertSame(
            $afterNotifiedAt->toDateTimeString(),
            $readingPlan->three_days_after_notified_at->toDateTimeString()
        );
    }

    public function test_読了済みの計画は期日を変更しても進行中に戻らない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => today()->addDays(5)->toDateString(),
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );

        $this->assertNotNull($readingPlan->completed_at);
    }

    public function test_読書計画が10件の場合は1ページ目に10件すべて表示される(): void
    {
        $user = User::factory()->create();

        $books = Book::factory()
            ->count(10)
            ->create();

        $books->each(function (Book $book) use ($user): void {
            $this->createReadingPlan($user, $book);
        });

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk();

        $readingPlans = $response->viewData('readingPlans');

        $this->assertInstanceOf(
            LengthAwarePaginator::class,
            $readingPlans
        );

        $this->assertCount(10, $readingPlans);
        $this->assertSame(10, $readingPlans->total());
        $this->assertSame(10, $readingPlans->perPage());
        $this->assertSame(1, $readingPlans->currentPage());
        $this->assertSame(1, $readingPlans->lastPage());
    }

    public function test_読書計画が11件の場合は10件ごとにページネーションされる(): void
    {
        $user = User::factory()->create();

        $books = Book::factory()
            ->count(11)
            ->create();

        $books->each(function (Book $book) use ($user): void {
            $this->createReadingPlan($user, $book);
        });

        $firstPageResponse = $this
            ->actingAs($user)
            ->get(route('reading-plans.index'));

        $firstPageResponse->assertOk();

        $firstPageReadingPlans = $firstPageResponse->viewData('readingPlans');

        $this->assertInstanceOf(
            LengthAwarePaginator::class,
            $firstPageReadingPlans
        );

        $this->assertCount(10, $firstPageReadingPlans);
        $this->assertSame(11, $firstPageReadingPlans->total());
        $this->assertSame(10, $firstPageReadingPlans->perPage());
        $this->assertSame(1, $firstPageReadingPlans->currentPage());
        $this->assertSame(2, $firstPageReadingPlans->lastPage());

        $secondPageResponse = $this
            ->actingAs($user)
            ->get(route('reading-plans.index', [
                'page' => 2,
            ]));

        $secondPageResponse->assertOk();

        $secondPageReadingPlans = $secondPageResponse->viewData('readingPlans');

        $this->assertInstanceOf(
            LengthAwarePaginator::class,
            $secondPageReadingPlans
        );

        $this->assertCount(1, $secondPageReadingPlans);
        $this->assertSame(11, $secondPageReadingPlans->total());
        $this->assertSame(10, $secondPageReadingPlans->perPage());
        $this->assertSame(2, $secondPageReadingPlans->currentPage());
        $this->assertSame(2, $secondPageReadingPlans->lastPage());
    }
}
