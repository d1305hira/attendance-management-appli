<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 会員登録ページが正常に表示されることを確認する
     */
    public function test_validate_name()
      {
        // 1. 会員登録ページを開く
        $getResponse = $this->get('/register');
        $getResponse->assertStatus(200);
        $getResponse->assertSee('会員登録');

        // 2. 名前を入力せず、他の必要項目を入力
        $formData = [
          // 'name' => '', // intentionally omitted
          'email' => 'user@example.com',
          'password' => 'securePassword123',
          'password_confirmation' => 'securePassword123',
          ];

        // 3. 登録ボタンを押す（POST送信）
        $postResponse = $this->post('/register', $formData);

        // バリデーションエラーを確認
        $postResponse->assertSessionHasErrors(['name']);

        // エラーメッセージが表示されているか確認
        $followed = $this->followRedirects($postResponse);
        $followed->assertSee('お名前を入力してください');
      }

		public function test_validate_email()
      {
        // 1. 会員登録ページを開く
        $getResponse = $this->get('/register');
        $getResponse->assertStatus(200);
        $getResponse->assertSee('会員登録');

        // 2. メールアドレスを入力せず、他の必要項目を入力
        $formData = [
          'name' => 'テストユーザー',
          // 'email' => '', // intentionally omitted
          'password' => 'securePassword123',
          'password_confirmation' => 'securePassword123',
          ];

        // 3. 登録ボタンを押す（POST送信）
        $postResponse = $this->post('/register', $formData);

        // バリデーションエラーを確認
        $postResponse->assertSessionHasErrors(['email']);

        // エラーメッセージが表示されているか確認
        $followed = $this->followRedirects($postResponse);
        $followed->assertSee('メールアドレスを入力してください');
      }


		public function test_validate_password()
      {
        // 1. 会員登録ページを開く
        $getResponse = $this->get('/register');
        $getResponse->assertStatus(200);
        $getResponse->assertSee('会員登録');

        // 2. パスワードを入力せず、他の必要項目を入力
        $formData = [
          'name' => 'テストユーザー',
          'email' => 'user@example.com',
				  // 'password' => '', // intentionally omitted
          ];

        // 3. 登録ボタンを押す（POST送信）
        $postResponse = $this->post('/register', $formData);

        // バリデーションエラーを確認
        $postResponse->assertSessionHasErrors(['password']);

        // エラーメッセージが表示されているか確認
        $followed = $this->followRedirects($postResponse);
        $followed->assertSee('パスワードを入力してください');
      }


		public function test_validate_password_length()
      {
        // 1. 会員登録ページを開く
        $getResponse = $this->get('/register');
        $getResponse->assertStatus(200);
        $getResponse->assertSee('会員登録');

        // 2. パスワードを７文字以下で入力し、他の必要項目を入力
        $formData = [
          'name' => 'テストユーザー',
          'email' => 'user@example.com',
          'password' => 'short7',// 7文字以下
          'password_confirmation' => 'short7',
          ];

        // 3. 登録ボタンを押す（POST送信）
        $postResponse = $this->post('/register', $formData);

        // バリデーションエラーを確認
        $postResponse->assertSessionHasErrors(['password']);

        // エラーメッセージが表示されているか確認
        $followed = $this->followRedirects($postResponse);
        $followed->assertSee('パスワードは8文字以上で入力してください');
      }


		public function test_validate_password_confirmation()
      {
        // 1. 会員登録ページを開く
        $getResponse = $this->get('/register');
        $getResponse->assertStatus(200);
        $getResponse->assertSee('会員登録');

        // 2. パスワードと確認用パスワードが一致しないデータを用意
        $formData = [
          'name' => 'テストユーザー',
          'email' => 'user@example.com',
          'password' => 'securePassword123',
          'password_confirmation' => 'differentPassword123',
          ];

        // 3. 登録ボタンを押す（POST送信）
        $postResponse = $this->post('/register', $formData);

        // バリデーションエラーを確認
        $postResponse->assertSessionHasErrors(['password']);

        // エラーメッセージが表示されているか確認
        $followed = $this->followRedirects($postResponse);
        $followed->assertSee('パスワードと一致しません');
      }

		public function test_validate_successful_registration_and_redirect_to_profile_edit()
      {
        // 1. 会員登録ページを開く
        $getResponse = $this->get('/register');
        $getResponse->assertStatus(200);
        $getResponse->assertSee('会員登録');

        // 2. 正常な入力データを用意
        $formData = [
          'name' => 'テストユーザー',
          'email' => 'user@example.com',
          'password' => 'securePassword123',
          'password_confirmation' => 'securePassword123',
          ];

        // 3. 登録ボタンを押す（POST送信）
        $postResponse = $this->post('/register', $formData);

        // 4. 登録後にプロフィール設定画面にリダイレクトされることを確認
        $postResponse->assertRedirect(route('attendance.index'));

        // 5. 実際にユーザーがDBに登録されていることを確認
        $this->assertDatabaseHas('users', [
          'name' => 'テストユーザー',
          'email' => 'user@example.com',
          ]);
      }
}