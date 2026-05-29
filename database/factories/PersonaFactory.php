<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PersonaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ci' => $this->faker->unique()->numerify('#######-1B'),
            'nombre' => $this->faker->firstName(),
            'ap_paterno' => $this->faker->lastName(),
            'ap_materno' => $this->faker->lastName(),
            'celular' => $this->faker->numerify('7#######'),
            'fecha_nacimiento' => $this->faker->date('Y-m-d', '-15 years'),
            'sexo' => $this->faker->randomElement(['M', 'F']),
            'estado' => true,
        ];
    }
}
