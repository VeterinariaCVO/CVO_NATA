<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // Admin
            [
                'name'     => 'Administrador CVO',
                'email'    => 'admin@cvo.com',
                'password' => Hash::make('password'),
                'role_id'  => 1,
                'phone'    => '6181234567',
                'address'  => 'Calle Principal 1, Gómez Palacio',
                'active'   => true,
            ],
            // Empleado
            [
                'name'     => 'María Recepción',
                'email'    => 'empleado@cvo.com',
                'password' => Hash::make('password'),
                'role_id'  => 2,
                'phone'    => '6182345678',
                'address'  => 'Av. Juárez 45, Gómez Palacio',
                'active'   => true,
            ],
            // Veterinario
            [
                'name'     => 'Dr. Carlos Veterinario',
                'email'    => 'vet@cvo.com',
                'password' => Hash::make('password'),
                'role_id'  => 4,
                'phone'    => '6183456789',
                'address'  => 'Blvd. Laguna 100, Torreón',
                'active'   => true,
            ],
            // Clientes
            [
                'name'     => 'Juan Pérez',
                'email'    => 'cliente1@cvo.com',
                'password' => Hash::make('password'),
                'role_id'  => 3,
                'phone'    => '6184567890',
                'address'  => 'Calle Roble 23, Gómez Palacio',
                'active'   => true,
            ],
            [
                'name'     => 'Ana García',
                'email'    => 'cliente2@cvo.com',
                'password' => Hash::make('password'),
                'role_id'  => 3,
                'phone'    => '6185678901',
                'address'  => 'Av. Tecnológico 88, Lerdo',
                'active'   => true,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], $user);
        }
    }
}
