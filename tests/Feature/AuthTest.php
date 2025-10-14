<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');

    $this->assertAuthenticated();

    $this->post('/logout');

    $response = $this->post('/login', ['email' => 'test@example.com', 'password' => 'secret123']);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }
}
