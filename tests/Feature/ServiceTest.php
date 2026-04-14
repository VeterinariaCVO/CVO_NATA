<?php

use App\Models\User;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\ServiceSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(ServiceSeeder::class);
});

test('Authenticated user can list services', function () {
    $user = User::where('email', 'cliente1@cvo.com')->first();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/services');

    $response
        ->assertOk()
        ->assertJsonIsObject();
});

test('Services response has expected structure', function () {
    $user = User::where('email', 'cliente1@cvo.com')->first();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/services');

    $response
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
});

test('Seeded services exist in database', function () {
    expect(Service::count())->toBeGreaterThan(0);
});

test('Admin route works for admin', function () {
    $user = User::where('email', 'admin@cvo.com')->first();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/admin/users');

    $response
        ->assertOk()
        ->assertJsonIsObject();
});

test('Admin route is forbidden for employee', function () {
    $user = User::where('email', 'empleado@cvo.com')->first();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/admin/users');

    $response->assertForbidden();
});

test('Client route works for client', function () {
    $user = User::where('email', 'cliente1@cvo.com')->first();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/mis-mascotas');

    $response
        ->assertOk()
        ->assertJsonIsObject();
});

test('Unauthenticated user cannot access me route', function () {
    $response = $this->getJson('/api/me');

    $response->assertUnauthorized();
});
