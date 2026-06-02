<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Persona;
use App\Models\Provincia;
use App\Models\Comunidad;
use App\Models\Curso;
use App\Models\CursoBth;
use App\Models\Tutor;
use App\Models\Gestion;
use App\Models\RegistroInternado;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EstudianteController extends Controller
{
    public function index()
    {
        $estudiantes = Estudiante::with([
            'persona',
            'comunidad.provincia',
            'tutor.persona',
            'registrosInternado.curso',
            'registrosInternado.cursoBth.carreraTecnica',
            'registrosInternado.gestion',
            'registrosInternado.boletines'
        ])->get();

        $cursos = Curso::all();
        $cursosBth = CursoBth::with('carreraTecnica')->get();
        $provincias = Provincia::with('comunidades')->get();
        $comunidades = Comunidad::with('provincia')->get();
        $gestiones = Gestion::orderBy('nombre_gestion', 'desc')->get();

        return Inertia::render('Admin/Estudiantes/Index', [
            'estudiantes' => $estudiantes,
            'cursos' => $cursos,
            'cursosBth' => $cursosBth,
            'provincias' => $provincias,
            'comunidades' => $comunidades,
            'gestiones' => $gestiones
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ci' => 'required|string|unique:personas,ci',
            'nombre' => 'required|string',
            'ap_paterno' => 'required|string',
            'ap_materno' => 'nullable|string',
            'celular' => 'nullable|string',
            'sexo' => 'nullable|string|in:M,F',
            'fecha_nacimiento' => 'nullable|date',

            // Student specific
            'comunidad_id' => 'nullable|exists:comunidades,id',
            'curso_id' => 'nullable|exists:cursos,id',
            'curso_bth_id' => 'nullable|exists:cursos_bth,id',
            'año_egreso_bth' => 'nullable|integer',
            'pabellon' => 'nullable|string|in:Pabellón Varones A,Pabellón Varones B,Pabellón Damas A,Pabellón Damas B',
            'cama' => 'nullable|string',

            // Tutor
            'tutor_ci' => 'required|string',
            'tutor_nombre' => 'required|string',
            'tutor_ap_paterno' => 'required|string',
            'tutor_ap_materno' => 'nullable|string',
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
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'estado' => true
            ]);

            // Tutor
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
            if ($request->tutor_celular && !$tutorPersona->celular) {
                $tutorPersona->update(['celular' => $request->tutor_celular]);
            }

            $tutor = Tutor::firstOrCreate(['persona_ci' => $tutorPersona->ci]);

            $estudiante = Estudiante::create([
                'persona_ci' => $persona->ci,
                'tutor_id' => $tutor->id,
                'comunidad_id' => $request->comunidad_id,
                'año_egreso_bth' => $request->año_egreso_bth,
                'estado_global' => 'Activo'
            ]);

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
        });

        return redirect()->back()->with('success', 'Estudiante creado exitosamente.');
    }

    public function update(Request $request, Estudiante $estudiante)
    {
        $request->validate([
            'nombre' => 'required|string',
            'ap_paterno' => 'required|string',
            'ap_materno' => 'nullable|string',
            'celular' => 'nullable|string',
            'sexo' => 'nullable|string|in:M,F',
            'fecha_nacimiento' => 'nullable|date',
            'comunidad_id' => 'nullable|exists:comunidades,id',
            'curso_id' => 'nullable|exists:cursos,id',
            'curso_bth_id' => 'nullable|exists:cursos_bth,id',
            'año_egreso_bth' => 'nullable|integer',
            'pabellon' => 'nullable|string',
            'cama' => 'nullable|string',
            'tutor_ci' => 'nullable|string',
            'tutor_nombre' => 'required_with:tutor_ci|nullable|string',
            'tutor_ap_paterno' => 'required_with:tutor_ci|nullable|string',
            'tutor_ap_materno' => 'nullable|string',
            'tutor_celular' => 'nullable|string',
            'estado_global' => 'nullable|string',
            'año_egreso_bachiller' => 'nullable|integer',
            'motivo_retiro' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $estudiante) {
            $persona = $estudiante->persona;
            $persona->update([
                'nombre' => $request->nombre,
                'ap_paterno' => $request->ap_paterno,
                'ap_materno' => $request->ap_materno,
                'celular' => $request->celular,
                'sexo' => $request->sexo,
                'fecha_nacimiento' => $request->fecha_nacimiento,
            ]);

            // Tutor
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
                $tutorPersona->update([
                    'nombre' => $request->tutor_nombre,
                    'ap_paterno' => $request->tutor_ap_paterno,
                    'ap_materno' => $request->tutor_ap_materno,
                    'celular' => $request->tutor_celular,
                ]);
                $tutor = Tutor::firstOrCreate(['persona_ci' => $tutorPersona->ci]);
                $estudiante->update(['tutor_id' => $tutor->id]);
            }

            $estudiante->update([
                'comunidad_id' => $request->comunidad_id,
                'año_egreso_bth' => $request->año_egreso_bth,
                'estado_global' => $request->estado_global,
                'año_egreso_bachiller' => $request->año_egreso_bachiller,
                'motivo_retiro' => $request->estado_global === 'Retirado' ? $request->motivo_retiro : $estudiante->motivo_retiro,
            ]);

            // Desactivar cuenta si se retira, y marcar registro de gestión activa como Retirado
            if ($request->estado_global === 'Retirado') {
                User::where('persona_ci', $estudiante->persona_ci)->update([
                    'estado_cuenta' => 'Pendiente'
                ]);

                $gestionActual = Gestion::where('estado', 'Activo')->first();
                if ($gestionActual) {
                    RegistroInternado::where('estudiante_id', $estudiante->id)
                        ->where('gestion_id', $gestionActual->id)
                        ->update(['estado_anual' => 'Retirado']);
                }
            }

            // Actualizar o crear registro en la gestión activa
            if ($request->curso_id) {
                $gestionActual = Gestion::where('estado', 'Activo')->first();
                if ($gestionActual) {
                    $registro = RegistroInternado::firstOrCreate([
                        'estudiante_id' => $estudiante->id,
                        'gestion_id' => $gestionActual->id,
                    ]);
                    $registro->update([
                        'curso_id' => $request->curso_id,
                        'curso_bth_id' => $request->curso_bth_id,
                        'pabellon' => $request->pabellon,
                        'cama' => $request->cama,
                        'estado_anual' => $request->estado_global === 'Retirado' ? 'Retirado' : 'Cursando'
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', 'Estudiante actualizado exitosamente.');
    }

    public function destroy(Request $request, Estudiante $estudiante)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->password, auth()->user()->password)) {
            return redirect()->back()->withErrors([
                'anular_password' => 'La contraseña de confirmación es incorrecta.'
            ]);
        }

        DB::transaction(function () use ($estudiante) {
            $personaCi = $estudiante->persona_ci;

            // 1. Eliminar Estudiante (elimina por cascada registros_internado, mensualidades, boletines, incidencias)
            $estudiante->delete();

            // 2. Eliminar cuenta de usuario vinculada
            User::where('persona_ci', $personaCi)->delete();

            // 3. Eliminar persona vinculada
            Persona::where('ci', $personaCi)->delete();
        });

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante y todos sus registros vinculados han sido eliminados permanentemente del sistema.');
    }

    public function subirFoto(Request $request, Estudiante $estudiante)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            if ($estudiante->ruta_foto) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $estudiante->ruta_foto));
            }

            $path = $request->file('foto')->store('fotos', 'public');
            
            $estudiante->update([
                'ruta_foto' => '/storage/' . $path
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'ruta_foto' => '/storage/' . $path
                ]);
            }

            return redirect()->back()->with('success', 'Foto de perfil actualizada exitosamente.');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo subir la foto.'
            ], 400);
        }

        return redirect()->back()->withErrors(['foto' => 'No se pudo subir la foto.']);
    }
}
