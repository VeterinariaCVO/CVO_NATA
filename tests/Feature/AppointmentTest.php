<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\WorkingDay;
use App\Models\User;
use App\Models\Pet;
use App\Models\Service;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->admin   = User::where('email', 'admin@cvo.com')->first();
    $this->cliente = User::where('email', 'cliente1@cvo.com')->first();
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


// 1
test('Crear cita exitosamente', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/appointments', [
        'pet_id'       => $this->pet->id,
        'time_slot_id' => $this->slot->id,
        'service_id'   => $this->service->id,
        'notes'        => 'Test cita',
    ]);

    $response->assertStatus(201);
    $response->assertJson(['message' => 'Cita registrada correctamente']);
});


// 2
test('Respuesta de citas es un array', function () {
    $response = $this->actingAs($this->admin)->getJson('/api/appointments');

    $response->assertOk();
    $response->assertJsonStructure(['data']);
});


// 3
test('Estructura correcta al obtener una cita', function () {
    $appointment = Appointment::create([
        'pet_id'       => $this->pet->id,
        'time_slot_id' => $this->slot->id,
        'service_id'   => $this->service->id,
        'status'       => 'pending',
        'is_walk_in'   => false,
        'notes'        => null,
        'created_by'   => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->getJson("/api/appointments/{$appointment->id}");

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => ['id', 'status', 'notes', 'pet', 'service']
    ]);
});


// 4
test('Obtener una cita por id', function () {
    $appointment = Appointment::create([
        'pet_id'       => $this->pet->id,
        'time_slot_id' => $this->slot->id,
        'service_id'   => $this->service->id,
        'status'       => 'pending',
        'is_walk_in'   => false,
        'notes'        => null,
        'created_by'   => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->getJson("/api/appointments/{$appointment->id}");

    $response->assertOk();
    $response->assertJsonFragment(['id' => $appointment->id]);
});


// 5
test('Rechaza crear cita en slot reservado', function () {
    $this->slot->update(['status' => 'reserved']);

    $response = $this->actingAs($this->admin)->postJson('/api/appointments', [
        'pet_id'       => $this->pet->id,
        'time_slot_id' => $this->slot->id,
        'service_id'   => $this->service->id,
    ]);

    $response->assertStatus(400);
    $response->assertJson(['message' => 'El horario seleccionado ya no está disponible.']);
});


// 6
test('Confirmar cita cambia status a confirmed', function () {
    $appointment = Appointment::create([
        'pet_id'       => $this->pet->id,
        'time_slot_id' => $this->slot->id,
        'service_id'   => $this->service->id,
        'status'       => 'pending',
        'is_walk_in'   => false,
        'notes'        => null,
        'created_by'   => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->putJson("/api/appointments/{$appointment->id}", [
        'status' => 'confirmed',
    ]);

    $response->assertOk();
    $response->assertJsonFragment(['status' => 'confirmed']);
});


// 7
test('Cancelar cita libera el slot', function () {
    $this->slot->update(['status' => 'reserved']);

    $appointment = Appointment::create([
        'pet_id'       => $this->pet->id,
        'time_slot_id' => $this->slot->id,
        'service_id'   => $this->service->id,
        'status'       => 'confirmed',
        'is_walk_in'   => false,
        'notes'        => null,
        'created_by'   => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->deleteJson("/api/appointments/{$appointment->id}");

    $response->assertOk();
    $response->assertJson(['message' => 'Cita cancelada correctamente']);
    $this->assertDatabaseHas('time_slots', ['id' => $this->slot->id, 'status' => 'available']);
});
