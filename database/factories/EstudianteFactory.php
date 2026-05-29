<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Persona;

class EstudianteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'persona_ci' => Persona::factory(),
            'colegio_origen' => 'Colegio ' . $this->faker->city(),
            'motivo_ingreso' => 'Estudios Secundarios',
            'estado_global' => 'Activo',
        ];
    }
}
