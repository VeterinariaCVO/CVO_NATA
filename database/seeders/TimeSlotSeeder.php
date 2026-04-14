<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WorkingDay;
use App\Models\TimeSlot;

class TimeSlotSeeder extends Seeder
{
    public function run(): void
    {
        // Horarios base: 9:00 a 18:00 en bloques de 30 minutos
        $slots = [
            ['start' => '09:00', 'end' => '09:30'],
            ['start' => '09:30', 'end' => '10:00'],
            ['start' => '10:00', 'end' => '10:30'],
            ['start' => '10:30', 'end' => '11:00'],
            ['start' => '11:00', 'end' => '11:30'],
            ['start' => '11:30', 'end' => '12:00'],
            ['start' => '12:00', 'end' => '12:30'],
            ['start' => '12:30', 'end' => '13:00'],
            ['start' => '13:00', 'end' => '13:30'],
            ['start' => '13:30', 'end' => '14:00'],
            ['start' => '16:00', 'end' => '16:30'],
            ['start' => '16:30', 'end' => '17:00'],
            ['start' => '17:00', 'end' => '17:30'],
            ['start' => '17:30', 'end' => '18:00'],
        ];

        $workingDays = WorkingDay::where('is_open', true)->get();

        foreach ($workingDays as $day) {
            foreach ($slots as $slot) {
                TimeSlot::updateOrCreate(
                    [
                        'working_day_id' => $day->id,
                        'start_time'     => $slot['start'],
                    ],
                    [
                        'end_time' => $slot['end'],
                        'status'   => 'available',
                    ]
                );
            }
        }
    }
}
