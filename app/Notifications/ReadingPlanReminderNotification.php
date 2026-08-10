<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    private ReadingPlan $readingPlan;

    private string $timing;

    public function __construct(ReadingPlan $readingPlan, string $timing)
    {
        $this->readingPlan = $readingPlan;
        $this->timing = $timing;
    }

    /**
     * 通知の送信チャンネル
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * 使用するメールの内容
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * 通知の内容
     *
     * @return array<string, mixed>
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

    private function title(): string
    {
        return match ($this->timing) {
            'three_days_before' => '読書計画の期日が近づいています',
            'on_due_date' => '読書計画の期日当日です',
            'three_days_after' => '読書計画の期日を過ぎています',
            default => '読書計画の通知',
        };
    }

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
