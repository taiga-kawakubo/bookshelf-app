<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = Carbon::today();

        $yamada = User::where('email', 'yamada@example.com')->firstOrFail();
        $suzuki = User::where('email', 'suzuki@example.com')->firstOrFail();

        $books = Book::orderBy('id')->take(11)->get()->values();

        $readingPlans = [
            // 期日前で通知前の計画
            [
                'user_id' => $yamada->id,
                'book_id' => $books[0]->id,
                'target_date' => $today->copy()->addDays(7),
                'status' => ReadingPlanStatus::InProgress,
            ],

            // 期日3日前の計画
            [
                'user_id' => $yamada->id,
                'book_id' => $books[1]->id,
                'target_date' => $today->copy()->addDays(3),
                'status' => ReadingPlanStatus::InProgress,
            ],

            // 期日3日前で通知を確認済みの計画
            [
                'user_id' => $yamada->id,
                'book_id' => $books[2]->id,
                'target_date' => $today->copy()->addDays(3),
                'status' => ReadingPlanStatus::InProgress,
            ],

            // 期日当日の計画
            [
                'user_id' => $yamada->id,
                'book_id' => $books[3]->id,
                'target_date' => $today->copy(),
                'status' => ReadingPlanStatus::InProgress,
            ],

            // 期日当日で通知を確認済みの計画
            [
                'user_id' => $yamada->id,
                'book_id' => $books[4]->id,
                'target_date' => $today->copy(),
                'status' => ReadingPlanStatus::InProgress,
            ],

            // 期日3日後で通知を確認済みの計画
            [
                'user_id' => $yamada->id,
                'book_id' => $books[5]->id,
                'target_date' => $today->copy()->subDays(3),
                'status' => ReadingPlanStatus::Overdue,
            ],

            // 期日3日後で通知を未確認の計画
            [
                'user_id' => $yamada->id,
                'book_id' => $books[6]->id,
                'target_date' => $today->copy()->subDays(3),
                'status' => ReadingPlanStatus::Overdue,
            ],

            // 期日を7日過ぎた計画
            [
                'user_id' => $yamada->id,
                'book_id' => $books[7]->id,
                'target_date' => $today->copy()->subDays(7),
                'status' => ReadingPlanStatus::Overdue,
            ],

            // 期限前に読了している計画
            [
                'user_id' => $yamada->id,
                'book_id' => $books[8]->id,
                'target_date' => $today->copy()->addDays(5),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => $today->copy(),
            ],

            // 期限後に読了している計画
            [
                'user_id' => $yamada->id,
                'book_id' => $books[9]->id,
                'target_date' => $today->copy()->subDays(5),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => $today->copy(),
            ],

            // 他ユーザーの通知表示確認用の計画
            [
                'user_id' => $suzuki->id,
                'book_id' => $books[10]->id,
                'target_date' => $today->copy()->addDays(7),
                'status' => ReadingPlanStatus::InProgress,
            ],
        ];

        collect($readingPlans)->each(function ($readingPlanData) {
            ReadingPlan::updateOrCreate(
                [
                    'user_id' => $readingPlanData['user_id'],
                    'book_id' => $readingPlanData['book_id'],
                ],
                [
                    'target_date' => $readingPlanData['target_date'],
                    'status' => $readingPlanData['status'],
                    'completed_at' => $readingPlanData['completed_at'] ?? null,
                    'three_days_before_notified_at' => null,
                    'on_due_date_notified_at' => null,
                    'three_days_after_notified_at' => null,
                ]
            );
        });
    }
}
