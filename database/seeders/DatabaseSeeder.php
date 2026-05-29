<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Rol;
use App\Models\Permiso;
use App\Models\Persona;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Curso;
use App\Models\RegistroInternado;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProvinciasSantaCruzSeeder::class,
            ComunidadesCordilleraSeeder::class,
            CarrerasBTHSeeder::class,
        ]);

        // Crear Roles
        $rolSuperadmin = Rol::create(['nombre' => 'Superadmin']);
        $rolEncargada = Rol::create(['nombre' => 'Encargada']);
        $rolEstudiante = Rol::create(['nombre' => 'Estudiante']);

        // Crear Permisos Base
        $permisoModificar = Permiso::create(['nombre' => 'modificar_datos_personales']);
        $permisoVerFinanzas = Permiso::create(['nombre' => 'ver_finanzas']);

        $rolSuperadmin->permisos()->attach([$permisoModificar->id, $permisoVerFinanzas->id]);
        $rolEncargada->permisos()->attach([$permisoVerFinanzas->id]);

        // Superadmin
        $personaAdmin = Persona::factory()->create([
            'nombre' => 'Admin',
            'ap_paterno' => 'Sistema',
            'ci' => '1000001'
        ]);
        User::factory()->create([
            'name' => 'Superadmin',
            'email' => 'admin@internado.test',
            'password' => Hash::make('password'),
            'persona_ci' => $personaAdmin->ci,
            'rol_id' => $rolSuperadmin->id,
        ]);

        // Encargada
        $personaEncargada = Persona::factory()->create([
            'nombre' => 'María',
            'ap_paterno' => 'Pérez',
            'ci' => '2000002'
        ]);
        User::factory()->create([
            'name' => 'Encargada Maria',
            'email' => 'encargada@internado.test',
            'password' => Hash::make('password'),
            'persona_ci' => $personaEncargada->ci,
            'rol_id' => $rolEncargada->id,
        ]);

        // Crear Gestión 2026
        $gestion = Gestion::create([
            'nombre_gestion' => '2026',
            'fecha_inicio' => '2026-02-01',
            'fecha_fin' => '2026-11-30',
            'tipo_periodo_academico' => 'Trimestre',
            'cantidad_periodos' => 3
        ]);

        // Crear Cursos
        $curso1 = Curso::create(['nombre' => '1ro Secundaria']);
        $curso2 = Curso::create(['nombre' => '2do Secundaria']);
        $curso3 = Curso::create(['nombre' => '3ro Secundaria']);
        $curso4 = Curso::create(['nombre' => '4to Secundaria']);
        $curso5 = Curso::create(['nombre' => '5to Secundaria']);
        $curso6 = Curso::create(['nombre' => '6to Secundaria']);

        // 2 Estudiantes
        for ($i = 1; $i <= 2; $i++) {
            $estudiante = Estudiante::factory()->create();
            
            User::factory()->create([
                'name' => $estudiante->persona->nombre . ' ' . $estudiante->persona->ap_paterno,
                'email' => 'estudiante' . $i . '@internado.test',
                'password' => Hash::make('password'),
                'persona_ci' => $estudiante->persona_ci,
                'rol_id' => $rolEstudiante->id,
            ]);

            // Inscribir al estudiante
            RegistroInternado::create([
                'estudiante_id' => $estudiante->id,
                'gestion_id' => $gestion->id,
                'curso_id' => $i === 1 ? $curso1->id : $curso2->id,
                'estado_anual' => 'Cursando',
                'observacion' => 'Inscripción de prueba'
            ]);
        }
    }
}
