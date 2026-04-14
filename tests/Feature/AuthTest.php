<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(UserSeeder::class);
});

test('Good Credentials', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'admin@cvo.com',
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJsonIsObject()
        ->assertJsonFragment([
            'success' => true,
            'message' => 'Login exitoso',
        ]);
});

test('Bad Credentials', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'admin@cvo.com',
        'password' => 'password_mal',
    ]);

    $response
        ->assertUnauthorized()
        ->assertJsonIsObject()
        ->assertJsonFragment([
            'success' => false,
            'message' => 'Correo o contraseña incorrectos',
        ]);
});

test('Authenticated Me Route', function () {
    $user = User::where('email', 'admin@cvo.com')->first();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/me');

    $response
        ->assertOk()
        ->assertJsonIsObject()
        ->assertJsonFragment([
            'success' => true,
            'message' => 'Usuario autenticado',
        ]);
});

test('Admin Route Allowed', function () {
    $user = User::where('email', 'admin@cvo.com')->first();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/admin/users');

    $response
        ->assertOk()
        ->assertJsonIsObject();
});

test('Employee Forbidden On Admin Route', function () {
    $user = User::where('email', 'empleado@cvo.com')->first();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/admin/users');

    $response->assertForbidden();
});

test('Client Route Allowed', function () {
    $user = User::where('email', 'cliente1@cvo.com')->first();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/mis-mascotas');

    $response
        ->assertOk()
        ->assertJsonIsObject();
});

test('Unauthenticated Route', function () {
    $response = $this->getJson('/api/me');

    $response->assertUnauthorized();
});
