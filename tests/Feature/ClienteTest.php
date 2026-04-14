<?php

use App\Models\User;
use App\Models\Pet;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function clienteAuth() {
    \DB::table('roles')->insert([
        'id'          => 3,
        'name'        => 'cliente',
        'description' => 'Cliente del sistema',
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $cliente = User::create([
        'name'     => 'Juan Pérez',
        'email'    => 'cliente@test.com',
        'password' => bcrypt('password123'),
        'role_id'  => 3,
        'active'   => true,
    ]);

    $token = $cliente->createToken('test')->plainTextToken;

    return [$cliente, $token];
}

test('cliente puede ver sus mascotas', function () {
    [$cliente, $token] = clienteAuth();

    Pet::create([
        'name'     => 'Max',
        'species'  => 'Perro',
        'sex'      => 'male',
        'owner_id' => $cliente->id,
        'active'   => true,
    ]);

    $this->withToken($token)
        ->getJson('/api/mis-mascotas')
        ->assertStatus(200)
        ->assertJsonIsObject();
});

test('cliente puede registrar una mascota', function () {
    [$cliente, $token] = clienteAuth();

    $this->withToken($token)
        ->postJson('/api/mis-mascotas', [
            'name'    => 'Luna',
            'species' => 'Gato',
            'sex'     => 'female',
        ])
        ->assertStatus(201)
        ->assertJsonFragment(['name' => 'Luna']);
});

test('cliente puede ver una mascota por id', function () {
    [$cliente, $token] = clienteAuth();

    $mascota = Pet::create([
        'name'     => 'Rocky',
        'species'  => 'Perro',
        'sex'      => 'male',
        'owner_id' => $cliente->id,
        'active'   => true,
    ]);

    $this->withToken($token)
        ->getJson("/api/mis-mascotas/{$mascota->id}")
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Rocky']);
});

test('cliente puede editar su mascota', function () {
    [$cliente, $token] = clienteAuth();

    $mascota = Pet::create([
        'name'     => 'Toby',
        'species'  => 'Perro',
        'sex'      => 'male',
        'owner_id' => $cliente->id,
        'active'   => true,
    ]);

    $this->withToken($token)
        ->putJson("/api/mis-mascotas/{$mascota->id}", [
            'name'    => 'Toby Updated',
            'species' => 'Perro',
            'sex'     => 'male',
        ])
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Toby Updated']);
});

test('cliente puede eliminar su mascota', function () {
    [$cliente, $token] = clienteAuth();

    $mascota = Pet::create([
        'name'     => 'Coco',
        'species'  => 'Gato',
        'sex'      => 'female',
        'owner_id' => $cliente->id,
        'active'   => true,
    ]);

    $this->withToken($token)
        ->deleteJson("/api/mis-mascotas/{$mascota->id}")
        ->assertStatus(200);
});

test('cliente no puede registrar mascota sin nombre ni especie', function () {
    [$cliente, $token] = clienteAuth();

    $this->withToken($token)
        ->postJson('/api/mis-mascotas', [])
        ->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['name', 'species'],
        ]);
});

test('cliente obtiene 404 al ver mascota inexistente', function () {
    [$cliente, $token] = clienteAuth();

    $this->withToken($token)
        ->getJson('/api/mis-mascotas/999')
        ->assertStatus(404);
});
