<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\ReadingPlan;
use App\Models\Book;
use App\Enums\ReadingPlanStatus;
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

        //読書ステータスによるフィルタ
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

        return view('reading-plans.create',compact('books'));
    }

    /**
     * 読書計画の登録
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        ReadingPlan::create([
            'user_id' =>$request->user()->id,
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

        $plan -> load('book');

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
        $plan->update([
            'target_date' => $validated['target_date']
        ]);

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
