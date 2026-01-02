<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
  {
    use RefreshDatabase;
    /**
     * A basic test example.
     *
     * @return void
     */

    //ログイン--メールアドレスバリデーション
    public function test_login_user_validate_email()
      {
        $response = $this->from('/login')->post(route('login'), [
          'email' => '',
          'password' => 'securePassword123',
          ]);

        // ログイン画面にリダイレクトされる
        $response->assertRedirect('/login');

        // セッションにバリデーションエラーが含まれている
        $response->assertSessionHasErrors(['email']);

        // 認証されていないことを確認
        $this->assertGuest();
      }

    //ログイン--パスワードバリデーション
    public function test_login_user_validate_password()
      {
        $response = $this->from('/login')->post(route('login'), [
          'email' => 'user@example.com',
          'password' => '',
          ]);

        // ログイン画面にリダイレクトされる
        $response->assertRedirect('/login');

        // セッションにバリデーションエラーが含まれている
        $response->assertSessionHasErrors(['password']);

        // 認証されていないことを確認
        $this->assertGuest();
      }
      //ログイン--未登録情報による認証失敗
    public function test_login_user_with_unregistered_information_fails_authentication()
    {
        $response = $this->from('/login')->post(route('login'), [
          'email' => 'notfound@example.com',
          'password' => 'invalidPassword123',
          ]);

        // ログイン画面にリダイレクトされる
        $response->assertRedirect('/login');

        // セッションに認証エラーが含まれている（LoginRequestではなく、Auth::attempt()の失敗）
        $response->assertSessionHasErrors(['email']);

        // 認証されていないことを確認
        $this->assertGuest();
    }
  }
