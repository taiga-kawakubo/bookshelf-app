<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\ReadingPlanStatus;

class ReadingPlan extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'target_date',
        'status',
    ];

    /**
     * キャストする値
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_date' => 'date',
        'status' => ReadingPlanStatus::class,
    ];

    /**
     * この読書計画と結びつく本を取得
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * この読書計画と結びつくユーザーを取得
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
