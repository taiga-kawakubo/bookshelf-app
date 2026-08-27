<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    private ReadingPlan $readingPlan;

    private string $timing;

    /**
     * 通知対象の読書計画と通知タイミングを受け取る
     *
     * @param  ReadingPlan  $readingPlan  通知対象の読書計画
     * @param  string  $timing  通知タイミング
     */
    public function __construct(ReadingPlan $readingPlan, string $timing)
    {
        $this->readingPlan = $readingPlan;
        $this->timing = $timing;
    }

    /**
     * 通知の送信チャンネル
     *
     * @param  object  $notifiable  通知を受け取る対象
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * データベースに保存する通知内容を返す
     *
     * @param  object  $notifiable  通知を受け取る対象
     * @return array<string, mixed> 通知データ
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_id' => $this->readingPlan->book_id,
            'book_title' => $this->readingPlan->book->title,
            'target_date' => $this->readingPlan->target_date->format('Y-m-d'),
            'timing' => $this->timing,
            'title' => $this->title(),
            'body' => $this->body(),
        ];
    }

    /**
     * 通知タイミングに応じた通知タイトルを返す
     *
     * @return string 通知タイトル
     */
    private function title(): string
    {
        return match ($this->timing) {
            'three_days_before' => '読書計画の期日が近づいています',
            'on_due_date' => '読書計画の期日当日です',
            'three_days_after' => '読書計画の期日を過ぎています',
            default => '読書計画の通知',
        };
    }

    /**
     * 通知タイミングに応じた通知本文を返す
     *
     * @return string 通知本文
     */
    private function body(): string
    {
        return match ($this->timing) {
            'three_days_before' => "「{$this->readingPlan->book->title}」の期日は３日後です。",
            'on_due_date' => "「{$this->readingPlan->book->title}」の期日は今日です。",
            'three_days_after' => "「{$this->readingPlan->book->title}」の期日を３日過ぎています。",
            default => '読書計画を確認してください。',
        };
    }
}
