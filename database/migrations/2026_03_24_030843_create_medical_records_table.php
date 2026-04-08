<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')
                  ->unique()                       // una cita = un solo registro clínico
                  ->constrained('appointments')
                  ->cascadeOnDelete();
            $table->foreignId('veterinarian_id')->constrained('users');
            $table->decimal('weight', 5, 2)->nullable();          // peso al momento de la consulta
            $table->decimal('temperature', 4, 1)->nullable();      // °C
            $table->text('symptoms')->nullable();                   // síntomas reportados
            $table->text('diagnosis')->nullable();                  // diagnóstico
            $table->text('treatment')->nullable();                  // tratamiento indicado
            $table->text('prescriptions')->nullable();              // recetas / medicamentos
            $table->text('observations')->nullable();               // observaciones adicionales
            $table->date('next_visit')->nullable();                 // próxima cita sugerida

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
