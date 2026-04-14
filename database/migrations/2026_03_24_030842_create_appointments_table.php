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
            $table->enum('status', [
                'pending',       // cita agendada, esperando confirmación
                'confirmed',     // cita confirmada
                'in_progress',   // en curso (walk-in entra directo aquí)
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
