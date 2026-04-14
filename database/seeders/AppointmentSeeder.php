<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Pet;
use App\Models\TimeSlot;
use App\Models\Service;
use App\Models\User;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $admin    = User::where('email', 'admin@cvo.com')->first();
        $service  = Service::where('name', 'Consulta médica')->first();
        $slots    = TimeSlot::where('status', 'available')->take(3)->get();
        $pets     = Pet::all();

        if ($pets->isEmpty() || $slots->isEmpty() || !$admin || !$service) {
            return;
        }

        $statuses = ['pending', 'confirmed', 'completed'];

        foreach ($slots as $index => $slot) {
            $pet = $pets[$index % $pets->count()];

            Appointment::updateOrCreate(
                ['time_slot_id' => $slot->id, 'pet_id' => $pet->id],
                [
                    'service_id'  => $service->id,
                    'status'      => $statuses[$index] ?? 'pending',
                    'is_walk_in'  => false,
                    'notes'       => 'Cita de prueba generada por seeder',
                    'created_by'  => $admin->id,
                ]
            );

            // Marcar slot como reservado
            $slot->update(['status' => 'reserved']);
        }

        // Una cita walk-in de ejemplo
        $availableSlot = TimeSlot::where('status', 'available')->first();
        $firstPet      = $pets->first();

        if ($availableSlot && $firstPet) {
            Appointment::updateOrCreate(
                ['time_slot_id' => $availableSlot->id, 'pet_id' => $firstPet->id, 'is_walk_in' => true],
                [
                    'service_id' => $service->id,
                    'status'     => 'in_progress',
                    'is_walk_in' => true,
                    'notes'      => 'Atención sin cita de prueba',
                    'created_by' => $admin->id,
                ]
            );

            $availableSlot->update(['status' => 'reserved']);
        }
    }
}
