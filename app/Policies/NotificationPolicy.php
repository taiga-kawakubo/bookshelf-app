<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationPolicy
{
    /**
     * 通知の送信先が通知を送る本人かを判定
     */
    public function markAsRead(User $user, DatabaseNotification $notification): bool
    {
        return $notification->notifiable_type === $user->getMorphClass()
        && (string) $notification->notifiable_id === (string) $user->getKey();
    }
}
