<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WorkingDay;

class WorkingDaySeeder extends Seeder
{
    public function run(): void
    {
        // Genera días laborales para las próximas 2 semanas
        // lunes a viernes solamente
        $days = [];
        $date = now()->startOfWeek(); // lunes de esta semana

        for ($week = 0; $week < 2; $week++) {
            for ($day = 0; $day < 5; $day++) {
                $days[] = [
                    'date'    => $date->copy()->addWeekdays($week * 5 + $day)->format('Y-m-d'),
                    'is_open' => true,
                ];
            }
        }

        foreach ($days as $day) {
            WorkingDay::updateOrCreate(['date' => $day['date']], $day);
        }
    }
}
