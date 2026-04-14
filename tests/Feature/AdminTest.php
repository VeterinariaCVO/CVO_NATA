<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Pet;
use App\Models\Service;
use App\Models\MedicalRecord;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\WorkingDay;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->admin   = User::where('email', 'admin@cvo.com')->first();
    $this->cliente = User::where('role_id', 3)->first();
    $this->pet     = Pet::where('owner_id', $this->cliente->id)->first();
    $this->service = Service::first();

    $this->day = WorkingDay::create([
        'date'    => '2099-06-15',
        'is_open' => true,
    ]);

    $this->slot = TimeSlot::create([
        'working_day_id' => $this->day->id,
        'start_time'     => '09:00:00',
        'end_time'       => '09:30:00',
        'status'         => 'available',
        'is_open'        => true,
    ]);
});


// Usuarios


test('GET /admin/users - Listar todos los usuarios', function () {
    $response = $this->actingAs($this->admin)->getJson('/api/admin/users');

    $response->assertOk();
    $response->assertJsonStructure(['data']);
});


test('POST /admin/users - Crear usuario válido', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/admin/users', [
        'name'     => 'Nuevo Usuario',
        'email'    => 'nuevo@cvo.com',
        'password' => 'password123',
        'role_id'  => 3,
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment(['name' => 'Nuevo Usuario']);
});


test('POST /admin/users - Sin nombre retorna 422', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/admin/users', [
        'email'    => 'nuevo@cvo.com',
        'password' => 'password123',
        'role_id'  => 3,
    ]);

    $response->assertStatus(422);
});


test('DELETE /admin/users/{id} - Eliminado correctamente', function () {
    $user = User::where('role_id', 3)->first();

    $response = $this->actingAs($this->admin)->deleteJson("/api/admin/users/{$user->id}");

    $response->assertOk();
    $response->assertJsonFragment(['message' => 'Usuario eliminado correctamente']);
});


// Mascotas


test('GET /admin1/pets - Listar todas las mascotas', function () {
    $response = $this->actingAs($this->admin)->getJson('/api/admin1/pets');

    $response->assertOk();
    $response->assertJsonStructure(['data']);
});


test('POST /admin/pets - Registrar mascota válida', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/admin/pets', [
        'name'     => 'Firulais',
        'species'  => 'Perro',
        'breed'    => 'Labrador',
        'owner_id' => $this->cliente->id,
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment(['name' => 'Firulais']);
});


test('DELETE /admin/pets/{id} - Eliminada correctamente', function () {
    $response = $this->actingAs($this->admin)->deleteJson("/api/admin/pets/{$this->pet->id}");

    $response->assertOk();
    $response->assertJsonFragment(['message' => 'Mascota eliminada exitosamente']);
});


// Servicios


test('GET /admin/services - Listar todos los servicios', function () {
    $response = $this->actingAs($this->admin)->getJson('/api/admin/services');

    $response->assertOk();
    $response->assertJsonStructure(['data']);
});


test('POST /admin/services - Crear servicio válido', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/admin/services', [
        'name'             => 'Consulta General',
        'description'      => 'Revisión médica completa',
        'price'            => 250.00,
        'duration_minutes' => 30,
        'active'           => true,
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment(['name' => 'Consulta General']);
});


test('POST /admin/services - Nombre duplicado retorna 422', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/admin/services', [
        'name'  => $this->service->name,
        'price' => 100.00,
    ]);

    $response->assertStatus(422);
});


// Historial Medico

// 28
test('GET /medical-records - Admin ve todos sin filtro', function () {
    $total = MedicalRecord::count();

    $response = $this->actingAs($this->admin)->getJson('/api/medical-records');

    $response->assertOk();
    $this->assertCount($total, $response->json('data'));
});


// Walk-in


test('POST /walk-in - Registrar walk-in válido sin time slot', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/walk-in', [
        'pet_id'     => $this->pet->id,
        'service_id' => $this->service->id,
        'notes'      => 'Consulta sin cita previa',
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment(['message' => 'Atención sin cita registrada correctamente.']);
});


test('POST /walk-in - Cita se guarda con status in_progress', function () {
    $this->actingAs($this->admin)->postJson('/api/walk-in', [
        'pet_id'     => $this->pet->id,
        'service_id' => $this->service->id,
    ]);

    $this->assertDatabaseHas('appointments', [
        'pet_id'     => $this->pet->id,
        'service_id' => $this->service->id,
        'status'     => 'in_progress',
        'is_walk_in' => true,
    ]);
});


// Perfil

test('GET /perfil - Ver perfil del admin autenticado', function () {
    $response = $this->actingAs($this->admin)->getJson('/api/perfil');

    $response->assertOk();
    $response->assertJsonFragment(['id' => $this->admin->id]);
});