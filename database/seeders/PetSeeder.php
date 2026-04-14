<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pet;
use App\Models\User;

class PetSeeder extends Seeder
{
    public function run(): void
    {
        $cliente1 = User::where('email', 'cliente1@cvo.com')->first();
        $cliente2 = User::where('email', 'cliente2@cvo.com')->first();

        if (!$cliente1 || !$cliente2) return;

        $pets = [
            [
                'name'     => 'Max',
                'species'  => 'Perro',
                'breed'    => 'Labrador',
                'color'    => 'Amarillo',
                'sex'      => 'male',
                'age'      => 3,
                'weight'   => 28.5,
                'owner_id' => $cliente1->id,
                'active'   => true,
            ],
            [
                'name'     => 'Luna',
                'species'  => 'Gato',
                'breed'    => 'Siamés',
                'color'    => 'Blanco y café',
                'sex'      => 'female',
                'age'      => 2,
                'weight'   => 4.2,
                'owner_id' => $cliente1->id,
                'active'   => true,
            ],
            [
                'name'     => 'Rocky',
                'species'  => 'Perro',
                'breed'    => 'Bulldog francés',
                'color'    => 'Atigrado',
                'sex'      => 'male',
                'age'      => 5,
                'weight'   => 12.0,
                'owner_id' => $cliente2->id,
                'active'   => true,
            ],
        ];

        foreach ($pets as $pet) {
            Pet::updateOrCreate(
                ['name' => $pet['name'], 'owner_id' => $pet['owner_id']],
                $pet
            );
        }
    }
}
