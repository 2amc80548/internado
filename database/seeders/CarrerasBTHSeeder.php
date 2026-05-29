<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarreraTecnica;
use App\Models\CursoBth;

class CarrerasBTHSeeder extends Seeder
{
    public function run(): void
    {
        $carreras = [
            'Sistemas Computacionales',
            'Agropecuaria',
            'Veterinaria',
            'Contabilidad',
            'Turismo',
            'Mecánica Automotriz'
        ];

        $niveles = [
            'Técnico Básico',
            'Técnico Auxiliar',
            'Técnico Medio'
        ];

        foreach ($carreras as $carreraNombre) {
            $carrera = CarreraTecnica::firstOrCreate([
                'nombre' => $carreraNombre
            ]);

            foreach ($niveles as $nivel) {
                CursoBth::firstOrCreate([
                    'carrera_tecnica_id' => $carrera->id,
                    'nivel' => $nivel
                ]);
            }
        }
    }
}
