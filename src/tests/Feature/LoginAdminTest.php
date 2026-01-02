<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;


class LoginAdminTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //管理者ログイン--メールアドレスバリデーション
    public function test_admin_login_email_required()
    {
      $response = $this->from('/admin/login')->post('/admin/login', [
        'email' => '',
        'password' => 'securePassword123',
      ]);

      $response->assertRedirect('/admin/login');
      $response->assertSessionHasErrors(['email']);
      $this->assertGuest('admin');
    }

    //管理者ログイン--パスワードバリデーション
    public function test_admin_login_password_required()
    {
      $response = $this->from('/admin/login')->post('/admin/login', [
        'email' => 'admin@example.com',
        'password' => '',
      ]);

      $response->assertRedirect('/admin/login');
      $response->assertSessionHasErrors(['password']);
      $this->assertGuest('admin');
    }

    //管理者ログイン--未登録情報による認証失敗
    public function test_admin_login_with_unregistered_information_fails_authentication()
    {
      $response = $this->from('/admin/login')->post('/admin/login', [
        'email' => 'notfound@example.com',
        'password' => 'invalidPassword123',
      ]);
      $response->assertRedirect('/admin/login');
      $response->assertSessionHasErrors(['email']);
      $this->assertGuest('admin');
    }
}