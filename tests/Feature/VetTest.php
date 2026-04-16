<?php

use App\Models\User;
use App\Models\Pet;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        \Database\Seeders\RoleSeeder::class,
        \Database\Seeders\UserSeeder::class,
        \Database\Seeders\ServiceSeeder::class,
        \Database\Seeders\WorkingDaySeeder::class,
        \Database\Seeders\TimeSlotSeeder::class,
        \Database\Seeders\PetSeeder::class,
        \Database\Seeders\AppointmentSeeder::class,
    ]);

    $this->vet      = User::where('email', 'vet@cvo.com')->first();
    $this->cliente1 = User::where('email', 'cliente1@cvo.com')->first();
    $this->admin    = User::where('email', 'admin@cvo.com')->first();
});

test('1 veterinario puede listar mascotas', function () {
    Sanctum::actingAs($this->vet);

    $this->getJson('/api/pets')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'species', 'sex', 'owner_id'],
            ],
        ]);
});

test('2 admin puede ver el detalle de una mascota', function () {
    Sanctum::actingAs($this->admin);

    $mascota = Pet::first();

    $this->getJson("/api/admin1/pets/{$mascota->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.id', $mascota->id);
});

test('3 veterinario no puede registrar mascotas', function () {
    Sanctum::actingAs($this->vet);

    $this->postJson('/api/pets', [
        'name'     => 'Intento',
        'species'  => 'Perro',
        'sex'      => 'male',
        'owner_id' => $this->cliente1->id,
    ])->assertStatus(403);
});

test('4 mascota no encontrada devuelve 404 para el admin', function () {
    Sanctum::actingAs($this->admin);

    $this->getJson('/api/admin1/pets/9999')
        ->assertStatus(404);
});

test('5 veterinario puede listar citas', function () {
    Sanctum::actingAs($this->vet);

    $this->getJson('/api/appointments')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'status', 'pet', 'service', 'time_slot'],
            ],
        ]);
});

test('6 veterinario solo recibe citas confirmadas en curso o completadas', function () {
    Sanctum::actingAs($this->vet);

    $response = $this->getJson('/api/appointments')->assertStatus(200);

    $estadosPermitidos = ['confirmed', 'in_progress', 'completed'];

    collect($response->json('data'))->each(function ($cita) use ($estadosPermitidos) {
        expect($estadosPermitidos)->toContain($cita['status']);
    });
});

test('7 veterinario puede listar sus expedientes medicos', function () {
    Sanctum::actingAs($this->vet);

    $this->getJson('/api/medical-records')
        ->assertStatus(200)
        ->assertJsonStructure(['data']);
});
