<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常な会員登録データを作成する。
     * テストごとに変更したい値は、$overrideで上書きする。
     *
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function validData(array $override = []): array
    {
        return array_merge([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ], $override);
    }

    public function test_有効な値で会員登録するとユーザーが作成されログイン画面へリダイレクトされる(): void
    {
        $response = $this->post(route('register'), $this->validData());

        $response->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);

        $user = User::query()
            ->where('email', 'yamada@example.com')
            ->firstOrFail();

        $this->assertSame('山田 太郎', $user->name);
        $this->assertSame('yamada@example.com', $user->email);
        $this->assertNotSame('Password1', $user->password);
        $this->assertTrue(Hash::check('Password1', $user->password));
    }

    public function test_必須項目が未入力の場合は会員登録できない(): void
    {
        $response = $this
            ->from(route('register'))
            ->post(route('register'), [
                'name' => '',
                'email' => '',
                'password' => '',
                'password_confirmation' => '',
            ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors([
            'name' => '名前を入力してください。',
            'email' => 'メールアドレスを入力してください。',
            'password' => 'パスワードを入力してください。',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_メールアドレスがメール形式ではない場合は会員登録できない(): void
    {
        $response = $this
            ->from(route('register'))
            ->post(route('register'), $this->validData([
                'email' => 'invalid-email',
            ]));

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスは正しい形式で入力してください。',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_登録済みメールアドレスでは会員登録できない(): void
    {
        User::factory()->create([
            'email' => 'yamada@example.com',
        ]);

        $response = $this
            ->from(route('register'))
            ->post(route('register'), $this->validData());

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors([
            'email' => '入力されたメールアドレスはすでに使用されています。',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_パスワードが8文字未満の場合は会員登録できない(): void
    {
        $response = $this
            ->from(route('register'))
            ->post(route('register'), $this->validData([
                'password' => 'Pass1',
                'password_confirmation' => 'Pass1',
            ]));

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください。',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_パスワード確認が一致しない場合は会員登録できない(): void
    {
        $response = $this
            ->from(route('register'))
            ->post(route('register'), $this->validData([
                'password_confirmation' => 'Password2',
            ]));

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors([
            'password' => 'パスワード確認と一致していません。',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_パスワードに数字が含まれない場合は会員登録できない(): void
    {
        $response = $this
            ->from(route('register'))
            ->post(route('register'), $this->validData([
                'password' => 'Password',
                'password_confirmation' => 'Password',
            ]));

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors([
            'password' => 'パスワードには少なくとも1文字の数字を含めてください。',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_パスワードに大文字と小文字が含まれない場合は会員登録できない(): void
    {
        $response = $this
            ->from(route('register'))
            ->post(route('register'), $this->validData([
                'password' => 'password1',
                'password_confirmation' => 'password1',
            ]));

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors([
            'password' => 'パスワードには大文字と小文字を含めてください。',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }
}
