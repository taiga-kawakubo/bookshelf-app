<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧の表示
     */
    public function index(IndexReadingPlanRequest $request): View
    {
        $validated = $request->validated();

        $currentStatus = $validated['status'] ?? null;

        // 読書ステータスによるフィルタ
        $readingPlans = ReadingPlan::query()
            ->with('book')
            ->where('user_id', $request->user()->id)
            ->when($currentStatus, function ($query) use ($currentStatus) {
                $query->where('status', $currentStatus);
            })
            ->latest()
            ->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書登録画面を表示
     */
    public function create(): View
    {
        $books = Book::query()->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画の登録
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        ReadingPlan::create([
            'user_id' => $request->user()->id,
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::InProgress,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を作成しました。');
    }

    /**
     * 読書編集画面を表示
     */
    public function edit(ReadingPlan $plan): View
    {
        $this->authorize('update', $plan);

        $plan->load('book');

        return view('reading-plans.edit', [
            'readingPlan' => $plan,
        ]);
    }

    /**
     * 読書計画を更新
     */
    public function update(UpdateReadingPlanRequest $request, ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $validated = $request->validated();

        $newTargetDate = Carbon::parse(
            $validated['target_date']
        );

        $targetDateChanged =
            $plan->target_date->toDateString()
            !== $newTargetDate->toDateString();

        $updates = [
            'target_date' => $newTargetDate,
        ];

        if ($targetDateChanged) {
            // 新しい期日に対して通知を再判定するため、通知履歴をリセットする
            $updates['three_days_before_notified_at'] = null;
            $updates['on_due_date_notified_at'] = null;
            $updates['three_days_after_notified_at'] = null;

            // 読了済み以外は、新しい期日に応じて状態を設定する
            if ($plan->status !== ReadingPlanStatus::Completed) {
                $updates['status'] = $newTargetDate->isBefore(today())
                    ? ReadingPlanStatus::Overdue->value
                    : ReadingPlanStatus::InProgress->value;
            }
        }

        $plan->update($updates);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画を削除
     */
    public function destroy(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);
        $plan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }

    /**
     *「 読了ボタン」を押す
     */
    public function complete(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $plan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書を完了しました。');
    }
}
