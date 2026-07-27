<?php

namespace Tests\Unit\Validate;

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

    private Genre $genre;

    /**
     * 各テストで使用する更新対象ジャンルを作成
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->genre = $this->createGenre('小説');
    }

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
    private function makeValidator(array $data): ValidationValidator
    {
        $request = new UpdateGenreRequest;

        /*
         * UpdateGenreRequest内の$this->route('genre')から更新対象ジャンルを取得できるようにする。
         */
        $route = new Route(['PUT'], 'genres/{genre}', []);

        // Routeのパラメータ配列を初期化する
        $route->bind($request);
        // bookパラメータを更新対象genreモデルへ置き換える
        $route->setParameter('genre', $this->genre);

        $request->setRouteResolver(fn () => $route);

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    /**
     * 正常なジャンル入力データを作成し、
     * 必要な項目だけ上書きできるようにする
     */
    private function validData(array $override = []): array
    {
        return array_merge([
            'name' => '文学',
        ], $override);
    }

    public function test_全ての項目が正しい場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_現在と同じジャンル名の場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'name' => $this->genre->name,
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_必須項目が送信されていない場合はバリデーションエラーになる(): void
    {
        $data = $this->validData();

        unset($data['name']);

        $validator = $this->makeValidator($data);

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('name')
        );
    }

    public function test_ジャンル名が文字列でない場合はバリデーションエラーになる(): void
    {
        $invalidNames = [
            ['文学'],
            123,
        ];

        foreach ($invalidNames as $name) {
            $validator = $this->makeValidator(
                $this->validData([
                    'name' => $name,
                ])
            );

            $this->assertTrue($validator->fails());
            $this->assertTrue(
                $validator->errors()->has('name')
            );
        }
    }

    public function test_ジャンル名が50文字の場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'name' => str_repeat('あ', 50),
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_ジャンル名が51文字の場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'name' => str_repeat('あ', 51),
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('name')
        );
    }

    public function test_別のジャンルと同じ名前の場合はバリデーションエラーになる(): void
    {
        $otherGenre = $this->createGenre('ミステリー');

        $validator = $this->makeValidator(
            $this->validData([
                'name' => $otherGenre->name,
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('name')
        );
    }
}
