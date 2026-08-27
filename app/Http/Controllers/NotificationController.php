<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * ログインユーザーの通知一覧を表示する。
     *
     * @return View 通知一覧画面
     */
    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->get();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 指定された通知を既読に変更する。
     *
     * @param  DatabaseNotification  $notification  既読にする通知
     * @return RedirectResponse 通知一覧画面へのリダイレクト
     */
    public function markAsRead(DatabaseNotification $notification): RedirectResponse
    {
        $this->authorize('markAsRead', $notification);

        $notification->markAsRead();

        return redirect()
            ->route('notifications.index')
            ->with('success', '通知を既読にしました。');
    }
}
