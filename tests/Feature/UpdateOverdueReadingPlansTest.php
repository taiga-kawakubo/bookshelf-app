<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateOverdueReadingPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_期日を過ぎた進行中の読書計画を期限超過に更新する(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('app:update-overdue-reading-plans')
            ->assertExitCode(0);

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Overdue,
            $readingPlan->status
        );
    }

    public function test_期限前の読書計画は期限超過に更新しない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDay(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('app:update-overdue-reading-plans')
            ->assertExitCode(0);

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $readingPlan->status
        );
    }

    public function test_期限当日の読書計画は期限超過に更新しない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('app:update-overdue-reading-plans')
            ->assertExitCode(0);

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $readingPlan->status
        );
    }

    public function test_読了済みの読書計画は期限超過に更新しない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDay(),
            'status' => ReadingPlanStatus::Completed,
        ]);

        $this->artisan('app:update-overdue-reading-plans')
            ->assertExitCode(0);

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );
    }
}
