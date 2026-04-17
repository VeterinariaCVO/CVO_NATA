<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained('pets')->cascadeOnDelete();
            $table->foreignId('time_slot_id')
                  ->nullable()
                  ->constrained('time_slots')
                  ->nullOnDelete();
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('vet_id')->nullable()->constrained('users')->nullOnDelete();

            // ¡AQUÍ ESTÁ LA MAGIA! Agregamos 'arrived' a la lista permitida
            $table->enum('status', [
                'pending',       // cita solicitada (internet), esperando confirmación
                'confirmed',     // cita confirmada (agendada), paciente por llegar
                'arrived',       // ¡NUEVO! paciente ya llegó y está en sala de espera
                'in_progress',   // en curso (con el veterinario)
                'completed',     // atención finalizada
                'cancelled',     // cancelada (solo antes de in_progress)
            ])->default('pending');

            $table->boolean('is_walk_in')->default(false); // distingue walk-in de cita agendada

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
