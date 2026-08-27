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
     * ログインユーザーの読書計画一覧をステータス条件に応じて表示する。
     *
     * @param  IndexReadingPlanRequest  $request  読書計画一覧の絞り込み条件
     * @return View 読書計画一覧画面
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
            ->paginate(10)
            ->withQueryString();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画作成画面を表示する。
     *
     * @return View 読書計画作成画面
     */
    public function create(): View
    {
        $books = Book::query()->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * ログインユーザーの読書計画を登録する。
     *
     * @param  StoreReadingPlanRequest  $request  読書計画登録リクエスト
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $targetDate = Carbon::parse($validated['target_date']);

        $status = $targetDate->isBefore(today())
            ? ReadingPlanStatus::Overdue
            : ReadingPlanStatus::InProgress;

        ReadingPlan::create([
            'user_id' => $request->user()->id,
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => $status,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を作成しました。');
    }

    /**
     * 指定された読書計画の編集画面を表示する。
     *
     * @param  ReadingPlan  $plan  編集対象の読書計画
     * @return View 読書計画編集画面
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
     * 指定された読書計画の期日を更新し、必要に応じて通知履歴と状態を再設定する。
     *
     * @param  UpdateReadingPlanRequest  $request  読書計画更新リクエスト
     * @param  ReadingPlan  $plan  更新対象の読書計画
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
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
     * 指定された読書計画を削除する。
     *
     * @param  ReadingPlan  $plan  削除対象の読書計画
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
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
     * 指定された読書計画を読了状態に変更する。
     *
     * @param  ReadingPlan  $plan  読了にする読書計画
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
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
