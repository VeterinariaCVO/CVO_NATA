<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'admin',       'description' => 'Administrador del sistema'],
            ['id' => 2, 'name' => 'empleado',     'description' => 'Recepcionista / empleado general'],
            ['id' => 3, 'name' => 'cliente',      'description' => 'Cliente dueño de mascota'],
            ['id' => 4, 'name' => 'veterinario',  'description' => 'Veterinario / médico'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['id' => $role['id']], $role);
        }
    }
}
