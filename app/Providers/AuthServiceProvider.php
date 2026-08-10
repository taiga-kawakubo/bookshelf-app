<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Policies\BookPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\ReadingPlanPolicy;
use App\Policies\ReviewPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\DatabaseNotification;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * ModelとPolicyの対応関係を定義
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Book::class => BookPolicy::class,
        Review::class => ReviewPolicy::class,
        ReadingPlan::class => ReadingPlanPolicy::class,
        DatabaseNotification::class => NotificationPolicy::class,
    ];

    /**
     * 認証・認可に関するサービスを登録する
     */
    public function boot(): void
    {
        //
    }
}
