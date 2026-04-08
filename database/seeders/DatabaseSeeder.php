<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            ServiceSeeder::class,
            WorkingDaySeeder::class,
            TimeSlotSeeder::class,
            PetSeeder::class,
            AppointmentSeeder::class,

        ]);
    }
}
