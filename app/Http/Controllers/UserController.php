<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Provincia;
use App\Models\Comunidad;
use App\Models\Curso;
use App\Models\CursoBth;
use App\Models\Tutor;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\RegistroInternado;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with([
            'persona', 
            'rol', 
            'persona.estudiante.comunidad',
            'persona.estudiante.tutor.persona',
            'persona.estudiante.registrosInternado.curso',
            'persona.estudiante.registrosInternado.cursoBth.carreraTecnica',
            'persona.estudiante.registrosInternado.gestion',
            'persona.estudiante.registrosInternado.boletines'
        ])->get();

        $roles = Rol::all();
        $provincias = Provincia::with('comunidades')->get();
        $comunidades = Comunidad::with('provincia')->get();
        $cursos = Curso::all();
        $cursosBth = CursoBth::with('carreraTecnica')->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'provincias' => $provincias,
            'comunidades' => $comunidades,
            'cursos' => $cursos,
            'cursosBth' => $cursosBth
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            // User validations
            'ci' => 'required|string|unique:personas,ci',
            'nombre' => 'required|string',
            'ap_paterno' => 'required|string',
            'ap_materno' => 'nullable|string',
            'rol_id' => 'required|exists:roles,id',
            'email' => 'required|email|unique:users,email',
            'celular' => 'nullable|string',
            'sexo' => 'nullable|string|in:M,F',

            // Student specific validations
            'is_estudiante' => 'boolean',
            'comunidad_id' => 'nullable|exists:comunidades,id',
            'curso_id' => 'nullable|exists:cursos,id',
            'curso_bth_id' => 'nullable|exists:cursos_bth,id',
            'año_egreso_bth' => 'nullable|integer',
            'pabellon' => 'nullable|string|in:Pabellón A,Pabellón B,Pabellón C,Pabellón D',
            'cama' => 'nullable|string',

            // Tutor validations
            'tutor_ci' => 'required_if:is_estudiante,true,1,"1"',
            'tutor_nombre' => 'required_if:is_estudiante,true,1,"1"',
            'tutor_ap_paterno' => 'required_if:is_estudiante,true,1,"1"',
            'tutor_celular' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $persona = Persona::create([
                'ci' => $request->ci,
                'nombre' => $request->nombre,
                'ap_paterno' => $request->ap_paterno,
                'ap_materno' => $request->ap_materno,
                'celular' => $request->celular,
                'sexo' => $request->sexo,
                'estado' => true
            ]);

            User::create([
                'name' => $request->nombre . ' ' . $request->ap_paterno,
                'email' => $request->email,
                'password' => Hash::make($request->ci),
                'persona_ci' => $persona->ci,
                'rol_id' => $request->rol_id
            ]);

            if ($request->is_estudiante) {
                // Crear o encontrar Tutor
                $tutorPersona = Persona::firstOrCreate(
                    ['ci' => $request->tutor_ci],
                    [
                        'nombre' => $request->tutor_nombre,
                        'ap_paterno' => $request->tutor_ap_paterno,
                        'ap_materno' => $request->tutor_ap_materno,
                        'celular' => $request->tutor_celular,
                        'estado' => true
                    ]
                );
                // Asegurar que si ya existía y no tenía celular, se le actualice
                if ($request->tutor_celular && !$tutorPersona->celular) {
                    $tutorPersona->update(['celular' => $request->tutor_celular]);
                }

                $tutor = Tutor::firstOrCreate(['persona_ci' => $tutorPersona->ci]);

                // Crear Estudiante
                $estudiante = Estudiante::create([
                    'persona_ci' => $persona->ci,
                    'tutor_id' => $tutor->id,
                    'comunidad_id' => $request->comunidad_id,
                    'año_egreso_bth' => $request->año_egreso_bth
                ]);

                // Registrar en la gestión actual si seleccionó un curso
                if ($request->curso_id) {
                    $gestionActual = Gestion::where('estado', 'Activo')->first();
                    if ($gestionActual) {
                        RegistroInternado::create([
                            'estudiante_id' => $estudiante->id,
                            'gestion_id' => $gestionActual->id,
                            'curso_id' => $request->curso_id,
                            'curso_bth_id' => $request->curso_bth_id,
                            'pabellon' => $request->pabellon,
                            'cama' => $request->cama,
                            'estado_anual' => 'Cursando'
                        ]);
                    }
                }
            }
        });

        return redirect()->back()->with('success', 'Usuario creado exitosamente.');
    }

    public function update(Request $request, User $user)
    {
        $selectedRole = Rol::find($request->rol_id);
        $isEstudiante = $selectedRole && $selectedRole->nombre === 'Estudiante';

        $rules = [
            'nombre' => 'required|string',
            'ap_paterno' => 'required|string',
            'ap_materno' => 'nullable|string',
            'rol_id' => 'required|exists:roles,id',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'celular' => 'nullable|string',
            'sexo' => 'nullable|string|in:M,F',
        ];

        if ($isEstudiante) {
            $rules = array_merge($rules, [
                'comunidad_id' => 'nullable|exists:comunidades,id',
                'curso_id' => 'nullable|exists:cursos,id',
                'curso_bth_id' => 'nullable|exists:cursos_bth,id',
                'año_egreso_bth' => 'nullable|integer',
                'pabellon' => 'nullable|string|in:Pabellón A,Pabellón B,Pabellón C,Pabellón D',
                'cama' => 'nullable|string',
                'tutor_ci' => 'nullable|string',
                'tutor_nombre' => 'required_with:tutor_ci|nullable|string',
                'tutor_ap_paterno' => 'required_with:tutor_ci|nullable|string',
                'tutor_ap_materno' => 'nullable|string',
                'tutor_celular' => 'nullable|string',
                'estado_global' => 'nullable|string',
                'año_egreso_bachiller' => 'nullable|integer',
            ]);
        }

        $request->validate($rules);

        DB::transaction(function () use ($request, $user, $isEstudiante) {
            $user->persona->update([
                'nombre' => $request->nombre,
                'ap_paterno' => $request->ap_paterno,
                'ap_materno' => $request->ap_materno,
                'celular' => $request->celular,
                'sexo' => $request->sexo,
            ]);

            $user->update([
                'name' => $request->nombre . ' ' . $request->ap_paterno,
                'email' => $request->email,
                'rol_id' => $request->rol_id
            ]);

            if ($user->estado_cuenta === 'Pendiente') {
                $user->update(['estado_cuenta' => 'Aprobado']);
            }

            if ($isEstudiante) {
                // 1. Crear o encontrar Tutor (Solo si se proporcionó CI de tutor)
                $tutorId = null;
                $estudiante = $user->persona->estudiante;
                if ($estudiante) {
                    $tutorId = $estudiante->tutor_id;
                }

                if ($request->tutor_ci) {
                    $tutorPersona = Persona::firstOrCreate(
                        ['ci' => $request->tutor_ci],
                        [
                            'nombre' => $request->tutor_nombre,
                            'ap_paterno' => $request->tutor_ap_paterno,
                            'ap_materno' => $request->tutor_ap_materno,
                            'celular' => $request->tutor_celular,
                            'estado' => true
                        ]
                    );

                    // Si ya existía, actualizar sus datos
                    $tutorPersona->update([
                        'nombre' => $request->tutor_nombre,
                        'ap_paterno' => $request->tutor_ap_paterno,
                        'ap_materno' => $request->tutor_ap_materno,
                        'celular' => $request->tutor_celular,
                    ]);

                    $tutor = Tutor::firstOrCreate(['persona_ci' => $tutorPersona->ci]);
                    $tutorId = $tutor->id;
                }

                // 2. Crear o actualizar Estudiante
                if (!$estudiante) {
                    $estudiante = Estudiante::create([
                        'persona_ci' => $user->persona->ci,
                        'tutor_id' => $tutorId,
                        'comunidad_id' => $request->comunidad_id,
                        'año_egreso_bth' => $request->año_egreso_bth,
                        'estado_global' => $request->estado_global ?? 'Activo',
                        'año_egreso_bachiller' => $request->año_egreso_bachiller,
                    ]);
                } else {
                    $estudiante->update([
                        'tutor_id' => $tutorId,
                        'comunidad_id' => $request->comunidad_id ?? $estudiante->comunidad_id,
                        'año_egreso_bth' => $request->año_egreso_bth,
                        'estado_global' => $request->estado_global ?? $estudiante->estado_global,
                        'año_egreso_bachiller' => $request->año_egreso_bachiller,
                    ]);
                }

                // 3. Crear o actualizar RegistroInternado en la gestión actual
                if ($request->curso_id) {
                    $gestionActual = Gestion::where('estado', 'Activo')->first();
                    if ($gestionActual) {
                        $registro = RegistroInternado::where('estudiante_id', $estudiante->id)
                            ->where('gestion_id', $gestionActual->id)
                            ->first();

                        if ($registro) {
                            $registro->update([
                                'curso_id' => $request->curso_id,
                                'curso_bth_id' => $request->curso_bth_id,
                                'pabellon' => $request->pabellon,
                                'cama' => $request->cama,
                            ]);
                        } else {
                            RegistroInternado::create([
                                'estudiante_id' => $estudiante->id,
                                'gestion_id' => $gestionActual->id,
                                'curso_id' => $request->curso_id,
                                'curso_bth_id' => $request->curso_bth_id,
                                'pabellon' => $request->pabellon,
                                'cama' => $request->cama,
                                'estado_anual' => 'Cursando'
                            ]);
                        }
                    }
                }
            }
        });

        return redirect()->back()->with('success', 'Usuario actualizado exitosamente.');
    }

    public function aprobarCuenta(User $user)
    {
        $user->update(['estado_cuenta' => 'Aprobado']);
        return redirect()->back()->with('success', 'Cuenta de usuario aprobada exitosamente.');
    }

    public function aprobarCuentasMasivo(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        User::whereIn('id', $request->user_ids)->update(['estado_cuenta' => 'Aprobado']);

        return redirect()->back()->with('success', 'Cuentas seleccionadas aprobadas exitosamente.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return redirect()->back()->with('success', 'Contraseña actualizada exitosamente.');
    }

    public function destroy(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, auth()->user()->password)) {
            return redirect()->back()->withErrors([
                'delete_password' => 'La contraseña de confirmación es incorrecta.'
            ]);
        }

        DB::transaction(function () use ($user) {
            if ($user->persona) {
                $user->persona()->delete();
            }
            $user->delete();
        });

        return redirect()->back()->with('success', 'Usuario eliminado exitosamente.');
    }
}
