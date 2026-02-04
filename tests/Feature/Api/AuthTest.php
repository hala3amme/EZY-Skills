<?php

use App\Enums\UserRole;
use App\Models\User;

test('student can register and receives token', function () {
    $payload = [
        'name' => 'Student One',
        'email' => 'student1@example.com',
        'phone_number' => '+1-555-0000',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $this->postJson('/api/auth/register', $payload)
        ->assertCreated()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'phone_number', 'role'],
        ]);

    expect(User::where('email', 'student1@example.com')->first()->role)->toBe(UserRole::Student);
});

test('user can login and receives token', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/auth/login', [
        'email' => 'login@example.com',
        'password' => 'password123',
        'device_name' => 'tests',
    ])
        ->assertOk()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'phone_number', 'role'],
        ]);
});
