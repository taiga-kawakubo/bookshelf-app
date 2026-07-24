<?php

namespace Tests\Unit;

use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use Tests\TestCase;

class UpdateGenreRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト用ジャンルを作成
     */
    private function createGenre(string $name): Genre
    {
        $genreId = DB::table('genres')->insertGetId([
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Genre::query()->findOrFail($genreId);
    }

    /**
     * UpdateGenreRequestのルールでバリデーターを作成
     */
    private function makeValidator(array $data, Genre $genre): ValidationValidator
    {
        $request = new UpdateGenreRequest;

        // UpdateGenreRequest内の$this->route('genre')で更新対象ジャンルを取得できるようにする。
        $route = new Route(['PUT'], 'genres/{genre}', []);
        $route->bind($request);
        $route->setParameter('genre', $genre);

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    /**
     * 正常なジャンル入力データを作成し、必要な項目だけ上書きできるようにする
     */
    private function validData(array $override = []): array
    {
        return array_merge([
            'name' => '文学',
        ], $override);
    }

    public function test_全ての項目が正しい場合はバリデーションを通過する(): void
    {
        $genre = $this->createGenre('小説');

        $validator = $this->makeValidator(
            $this->validData(),
            $genre
        );

        $this->assertFalse($validator->fails());
    }

    public function test_現在と同じジャンル名の場合はバリデーションを通過する(): void
    {
        $genre = $this->createGenre('小説');

        $validator = $this->makeValidator(
            $this->validData([
                'name' => '小説',
            ]),
            $genre
        );

        $this->assertFalse($validator->fails());
    }

    public function test_必須項目が送信されていない場合はバリデーションエラーになる(): void
    {
        $genre = $this->createGenre('小説');

        $data = $this->validData();

        unset($data['name']);

        $validator = $this->makeValidator(
            $data,
            $genre
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('name')
        );
    }

    public function test_ジャンル名が文字列でない場合はバリデーションエラーになる(): void
    {
        $genre = $this->createGenre('小説');

        $invalidNames = [
            ['文学'],
            123,
        ];

        foreach ($invalidNames as $name) {
            $validator = $this->makeValidator(
                $this->validData([
                    'name' => $name,
                ]),
                $genre
            );

            $this->assertTrue($validator->fails());
            $this->assertTrue(
                $validator->errors()->has('name')
            );
        }
    }

    public function test_ジャンル名が50文字の場合はバリデーションを通過する(): void
    {
        $genre = $this->createGenre('小説');

        $validator = $this->makeValidator(
            $this->validData([
                'name' => str_repeat('あ', 50),
            ]),
            $genre
        );

        $this->assertFalse($validator->fails());
    }

    public function test_ジャンル名が51文字の場合はバリデーションエラーになる(): void
    {
        $genre = $this->createGenre('小説');

        $validator = $this->makeValidator(
            $this->validData([
                'name' => str_repeat('あ', 51),
            ]),
            $genre
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('name')
        );
    }

    public function test_別のジャンルと同じ名前の場合はバリデーションエラーになる(): void
    {
        $genre = $this->createGenre('小説');

        $this->createGenre('ミステリー');

        $validator = $this->makeValidator(
            $this->validData([
                'name' => 'ミステリー',
            ]),
            $genre
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('name')
        );
    }
}
