<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name'             => 'Consulta médica',
                'description'      => 'Revisión general con veterinario',
                'price'            => 350.00,
                'duration_minutes' => 30,
                'active'           => true,
            ],
            [
                'name'             => 'Vacunación',
                'description'      => 'Aplicación de vacunas según calendario',
                'price'            => 200.00,
                'duration_minutes' => 15,
                'active'           => true,
            ],
            [
                'name'             => 'Cirugía',
                'description'      => 'Procedimientos quirúrgicos',
                'price'            => 1500.00,
                'duration_minutes' => 120,
                'active'           => true,
            ],
            [
                'name'             => 'Guardería',
                'description'      => 'Servicio de daycare para mascotas',
                'price'            => 150.00,
                'duration_minutes' => 480,
                'active'           => true,
            ],
            [
                'name'             => 'Desparasitación',
                'description'      => 'Tratamiento antiparasitario interno y externo',
                'price'            => 180.00,
                'duration_minutes' => 20,
                'active'           => true,
            ],
            [
                'name'             => 'Baño y estética',
                'description'      => 'Grooming completo: baño, corte y secado',
                'price'            => 250.00,
                'duration_minutes' => 60,
                'active'           => true,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(['name' => $service['name']], $service);
        }
    }
}
