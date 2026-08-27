<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * 書籍数を含めたジャンル一覧を表示する。
     *
     * @return View ジャンル一覧画面
     */
    public function index(): View
    {
        $genres = Genre::query()
            ->withCount('books')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル作成画面を表示する。
     *
     * @return View ジャンル作成画面
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンルを登録する。
     *
     * @param  StoreGenreRequest  $request  ジャンル登録リクエスト
     * @return RedirectResponse ジャンル一覧画面へのリダイレクト
     */
    public function store(StoreGenreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Genre::create([
            'name' => $validated['name'],
        ]);

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを登録しました。');
    }

    /**
     * 指定されたジャンルに紐づく書籍一覧を表示する。
     *
     * @param  Genre  $genre  表示対象のジャンル
     * @return View ジャンル詳細画面
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()
            ->with(['genres:id,name'])
            ->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * 指定されたジャンルの編集画面を表示する。
     *
     * @param  Genre  $genre  編集対象のジャンル
     * @return View ジャンル編集画面
     */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * 指定されたジャンルを更新する。
     *
     * @param  UpdateGenreRequest  $request  ジャンル更新リクエスト
     * @param  Genre  $genre  更新対象のジャンル
     * @return RedirectResponse ジャンル一覧画面へのリダイレクト
     */
    public function update(UpdateGenreRequest $request, Genre $genre): RedirectResponse
    {
        $validated = $request->validated();

        $genre->update([
            'name' => $validated['name'],
        ]);

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを更新しました。');
    }

    /**
     * 書籍に使用されていないジャンルを削除する。
     *
     * @param  Genre  $genre  削除対象のジャンル
     * @return RedirectResponse ジャンル一覧画面へのリダイレクト
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        if ($genre->books()->exists()) {
            return redirect()
                ->route('genres.index')
                ->with(
                    'error', 'このジャンルは書籍に使用されているため削除できません。'
                );
        }

        $genre->delete();

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを削除しました。');
    }
}
