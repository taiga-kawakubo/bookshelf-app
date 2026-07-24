<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    public function index(): View
    {
        $genres = Genre::query()
            ->withCount('books')
            ->orderBy('id')
            ->get();

        return view('genres.index', compact('genres'));
    }

    public function create(): View
    {
        return view('genres.create');
    }

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

    public function show(Genre $genre): View
    {
        $books = $genre->books()
            ->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

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
