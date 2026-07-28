<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * 検証に必要なユーザーを作成する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'yamada@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_ログイン画面が表示できる(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_未認証ユーザーが新規登録ページにアクセスできる(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_正しい認証情報でログインできる(): void
    {
        $response = $this->post(
            route('login.store'),
            [
                'email' => $this->user->email,
                'password' => 'password',
            ]
        );

        $response->assertRedirect(route('books.index'));

        $response->assertSessionHas(
            'success',
            'ログインしました。'
        );

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_存在しないメールアドレスではログインに失敗する(): void
    {
        $response = $this
            ->from(route('login'))
            ->post(
                route('login.store'),
                [
                    'email' => 'notfound@example.com',
                    'password' => 'password',
                ]
            );

        $response->assertRedirect(route('login'));

        $response->assertSessionHasErrors([
            'email' => __('auth.failed'),
        ]);

        $this->assertGuest();
    }

    public function test_間違ったパスワードではログインに失敗する(): void
    {
        $response = $this
            ->from(route('login'))
            ->post(
                route('login.store'),
                [
                    'email' => $this->user->email,
                    'password' => 'notfoundpassword',
                ]
            );

        $response->assertRedirect(route('login'));

        $response->assertSessionHasErrors([
            'email' => __('auth.failed'),
        ]);

        $this->assertGuest();
    }

    public function test_メールアドレスが空の場合はバリデーションエラーになる(): void
    {
        $response = $this
            ->from(route('login'))
            ->post(
                route('login.store'),
                [
                    'email' => '',
                    'password' => 'password',
                ]
            );

        $response->assertRedirect(route('login'));

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください。',
        ]);

        $this->assertGuest();
    }

    public function test_パスワードが空の場合はバリデーションエラーになる(): void
    {
        $response = $this
            ->from(route('login'))
            ->post(
                route('login.store'),
                [
                    'email' => $this->user->email,
                    'password' => '',
                ]
            );

        $response->assertRedirect(route('login'));

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください。',
        ]);

        $this->assertGuest();
    }

    public function test_パスワードが文字列でない場合はバリデーションエラーになる(): void
    {
        $response = $this
            ->from(route('login'))
            ->post(
                route('login.store'),
                [
                    'email' => $this->user->email,
                    'password' => ['password'],
                ]
            );

        $response->assertRedirect(route('login'));

        $response->assertSessionHasErrors([
            'password' => 'パスワードは文字列で入力してください。',
        ]);

        $this->assertGuest();
    }

    public function test_ログアウトできる(): void
    {
        $this->actingAs($this->user);

        $this->assertAuthenticatedAs($this->user);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->get(route('favorites.index'))
            ->assertRedirect(route('login'));
    }

    public function test_ログイン済みユーザーがログイン画面へアクセスすると書籍一覧へリダイレクトされる(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(route('login'));

        $response->assertRedirect(route('books.index'));
    }

    public function test_ログイン済みユーザーが新規登録画面へアクセスすると書籍一覧へリダイレクトされる(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(route('register'));

        $response->assertRedirect(route('books.index'));
    }
}
