<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Provincia;

class ProvinciasSantaCruzSeeder extends Seeder
{
    public function run(): void
    {
        $provincias = [
            'Andrés Ibáñez',
            'Ignacio Warnes',
            'José Miguel de Velasco',
            'Ichilo',
            'Chiquitos',
            'Sara',
            'Cordillera',
            'Vallegrande',
            'Florida',
            'Obispo Santistevan',
            'Ñuflo de Chávez',
            'Ángel Sandoval',
            'Manuel María Caballero',
            'Germán Busch',
            'Guarayos'
        ];

        foreach ($provincias as $provincia) {
            Provincia::firstOrCreate(['nombre' => $provincia]);
        }
    }
}
