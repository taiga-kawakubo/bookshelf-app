<?php

namespace Database\Seeders;

use App\Models\ReadingPlan;
use App\Models\User;
use App\Models\Book;
use App\Enums\ReadingPlanStatus;
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

        $books = Book::orderBy('id')->take(7)->get();

        $readingPlans = [
            //期日まで余裕がある計画
            [
                'user_id' => $yamada->id,
                'book_id' =>$books[0]->id,
                'target_date'=>$today->copy()->addDays(7),
                'status' => ReadingPlanStatus::InProgress,
            ],

            //期日前日の計画
            [
                'user_id' => $yamada->id,
                'book_id' => $books[1]->id,
                'target_date'=>$today->copy()->addDays(1),
                'status' => ReadingPlanStatus::InProgress,
            ],

            //期日当日の計画
            [
                'user_id' => $yamada->id,
                'book_id' =>$books[2]->id,
                'target_date'=>$today->copy(),
                'status' => ReadingPlanStatus::InProgress,
            ],

            //読了済みの期日前の計画
            [
                'user_id' => $yamada->id,
                'book_id' =>$books[3]->id,
                'target_date'=>$today->copy()->addDays(1),
                'status' => ReadingPlanStatus::Completed,
            ],

            //期日を過ぎた計画
            [
                'user_id' => $yamada->id,
                'book_id' =>$books[4]->id,
                'target_date'=>$today->copy()->subDays(1),
                'status' => ReadingPlanStatus::Overdue,
            ],

            //読了済みの過去の計画
            [
                'user_id' => $yamada->id,
                'book_id' =>$books[5]->id,
                'target_date'=>$today->copy()->subDays(3),
                'status' => ReadingPlanStatus::Completed,
            ],

            //suzukiの計画
            [
                'user_id' => $suzuki->id,
                'book_id' =>$books[6]->id,
                'target_date'=>$today->copy()->addDays(7),
                'status' => ReadingPlanStatus::InProgress,
            ],

            //期日を過ぎた進行中の計画
            [
                'user_id' => $yamada->id,
                'book_id' => $books[6]->id,
                'target_date' => $today->copy()->subDay(),
                'status' => ReadingPlanStatus::InProgress,
            ],
        ];

        collect($readingPlans)->each(function($readingPlanData) {
            ReadingPlan::firstOrCreate(
                [
                    'user_id' => $readingPlanData['user_id'],
                    'book_id' => $readingPlanData['book_id'],
                    'target_date' => $readingPlanData['target_date'],
                    'status' => $readingPlanData['status'],
                ]
            );
        });
    }
}