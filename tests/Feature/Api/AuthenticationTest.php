<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

test('user can register with valid data', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'token',
            'user' => ['id', 'name', 'email'],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});

test('registration fails with invalid email', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'John Doe',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('registration fails with mismatched passwords', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('registration fails with duplicate email', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $response = $this->postJson('/api/v1/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'token',
            'user',
        ]);
});

test('login fails with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'john@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'The provided credentials are incorrect.',
        ]);
});

test('user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/logout');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Logout successful.',
        ]);

    // Verify token is deleted
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
    ]);
});

test('logout requires authentication', function () {
    $response = $this->postJson('/api/v1/logout');

    $response->assertStatus(401);
});

test('user can request password reset', function () {
    \Illuminate\Support\Facades\Notification::fake();
    $user = User::factory()->create(['email' => 'john@example.com']);

    $response = $this->postJson('/api/v1/forgot-password', [
        'email' => 'john@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Password reset link sent to your email.',
        ]);
});

test('password reset request fails for non-existent email', function () {
    $response = $this->postJson('/api/v1/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('user can reset password with valid token', function () {
    $user = User::factory()->create(['email' => 'john@example.com']);
    $token = Password::createToken($user);

    $response = $this->postJson('/api/v1/reset-password', [
        'email' => 'john@example.com',
        'token' => $token,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Password reset successfully.',
        ]);

    // Verify password was changed
    $user->refresh();
    $this->assertTrue(Hash::check('newpassword123', $user->password));
});

test('password reset fails with invalid token', function () {
    $user = User::factory()->create(['email' => 'john@example.com']);

    $response = $this->postJson('/api/v1/reset-password', [
        'email' => 'john@example.com',
        'token' => 'invalid-token',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Invalid or expired password reset token.',
        ]);
});
