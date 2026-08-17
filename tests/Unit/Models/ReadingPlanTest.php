<?php

namespace Tests\Unit\Models;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_読書計画は結びつく書籍を取得する(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '読書計画の対象書籍',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-07-31',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $readingPlan->load('book');

        $this->assertTrue(
            $readingPlan->book->is($book)
        );
    }

    public function test_読書計画は結びつくユーザーを取得する(): void
    {
        $user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-07-31',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $readingPlan->load('user');

        $this->assertTrue(
            $readingPlan->user->is($user)
        );
    }
}
