<?php
//php artisan db:seed --class=EstudiantesMasivosSeeder
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Rol;
use App\Models\Persona;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Curso;
use App\Models\RegistroInternado;
use App\Models\Mensualidad;
use App\Models\Comunidad;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class EstudiantesMasivosSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES'); // Faker en español para nombres reales

        // 1. Obtener datos clave de tu base de datos actual
        $rolEstudiante = Rol::where('nombre', 'Estudiante')->first();
        $gestionActual = Gestion::where('nombre_gestion', '2026')->first() ?? Gestion::first();
        $cursos = Curso::all();
        $comunidades = Comunidad::all();

        if ($cursos->isEmpty() || !$rolEstudiante || !$gestionActual) {
            $this->command->error("Por favor, asegúrate de correr primero DatabaseSeeder para tener los roles, cursos y gestiones.");
            return;
        }

        $meses = ['Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre'];

        $this->command->info("Generando 20 estudiantes al azar... Por favor espera.");

        // 2. Generar los 300 estudiantes en un bucle
        for ($i = 1; $i <= 100; $i++) {

            // Decidir sexo de forma aleatoria (para pabellón coherente)
            $sexo = $faker->randomElement(['M', 'F']);
            $nombre = $sexo === 'M' ? $faker->firstNameMale() : $faker->firstNameFemale();

            // Crear Persona
            $persona = Persona::create([
                'nombre' => $nombre,
                'ap_paterno' => $faker->lastName(),
                'ap_materno' => $faker->lastName(),
                'ci' => $faker->unique()->numberBetween(5000000, 9999999), // CI único y ficticio
                'sexo' => $sexo,
                'celular' => $faker->optional(0.7)->numberBetween(60000000, 79999999),
                'fecha_nacimiento' => $faker->date('Y-m-d', '-12 years'), // Edades de colegio
            ]);

            // Decidir al azar si la cuenta de usuario nace Aprobada (80%) o Pendiente (20%)
            $estadoCuenta = $faker->boolean(80) ? 'Aprobado' : 'Pendiente';

            // Crear el Usuario
            $user = User::create([
                'name' => $persona->nombre . ' ' . $persona->ap_paterno,
                'email' => 'estudiante' . $persona->ci . '@internado.com', // Email ficticio basado en CI
                'password' => Hash::make('12345678'), // Contraseña por defecto
                'persona_ci' => $persona->ci,
                'rol_id' => $rolEstudiante->id,
                'estado_cuenta' => $estadoCuenta,
            ]);

            // Crear Estudiante
            $estudiante = Estudiante::create([
                'persona_ci' => $persona->ci,
                'comunidad_id' => $comunidades->isNotEmpty() ? $comunidades->random()->id : null,
                'estado_global' => 'Activo', // Alumnos activos en el internado
            ]);

            // Asignar Pabellón coherente según sexo
            $pabellon = $sexo === 'M' ? 'Pabellón Varones ' . $faker->randomElement(['A', 'B']) : 'Pabellón Damas ' . $faker->randomElement(['A', 'B']);

            // Crear Inscripción Académica (RegistroInternado)
            $registro = RegistroInternado::create([
                'estudiante_id' => $estudiante->id,
                'gestion_id' => $gestionActual->id,
                'curso_id' => $cursos->random()->id, // Curso al azar
                'estado_anual' => 'Cursando',
                'pabellon' => $pabellon,
                'cama' => $faker->numberBetween(1, 80),
                'observacion' => 'Estudiante masivo de prueba'
            ]);

            // 3. Crear las Mensualidades y simular deudas al azar
            foreach ($meses as $mes) {
                // Simular que el 60% de sus mensualidades ya están "Pagadas" y el 40% están "Pendientes" (deuda)
                $estadoPago = $faker->boolean(60) ? 'Pagado' : 'Pendiente';

                Mensualidad::create([
                    'registro_internado_id' => $registro->id,
                    'mes' => $mes,
                    'monto' => 50.00, // Monto estándar
                    'total' => 50.00,
                    'estado' => $estadoPago,
                    'metodo_pago' => $estadoPago === 'Pagado' ? $faker->randomElement(['QR', 'Efectivo']) : null,
                    'fecha_pago' => $estadoPago === 'Pagado' ? $faker->dateTimeBetween('-4 months', 'now')->format('Y-m-d') : null,
                ]);
            }
        }

        $this->command->info("¡20 estudiantes creados con éxito de forma limpia!");
    }
}
