<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use App\Http\Requests\StoreGestionRequest;
use App\Http\Requests\UpdateGestionRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GestionController extends Controller
{
    public function index()
    {
        $gestiones = Gestion::latest()->get();
        return Inertia::render('Gestiones/Index', [
            'gestiones' => $gestiones
        ]);
    }

    public function create()
    {
        return Inertia::render('Gestiones/Create');
    }

    public function store(StoreGestionRequest $request)
    {
        Gestion::create($request->validated());

        return redirect()->route('gestiones.index')->with('success', 'Gestión creada exitosamente.');
    }

    public function show(Gestion $gestion)
    {
        return Inertia::render('Gestiones/Show', [
            'gestion' => $gestion->load('registrosInternado')
        ]);
    }

    public function edit(Gestion $gestion)
    {
        return Inertia::render('Gestiones/Edit', [
            'gestion' => $gestion
        ]);
    }

    public function update(Request $request, Gestion $gestion)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
        ]);

        $gestion->update([
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin
        ]);

        return redirect()->back()->with('success', 'Fechas de gestión actualizadas exitosamente.');
    }

    public function destroy(Gestion $gestion)
    {
        $gestion->delete();

        return redirect()->route('gestiones.index')->with('success', 'Gestión eliminada exitosamente.');
    }

    public function ofertar(Request $request)
    {
        $request->validate([
            'nombre_gestion' => 'required|string|unique:gestiones,nombre_gestion',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'tipo_periodo_academico' => 'required|string',
            'amount_periods' => 'nullable|integer', // compatible option
            'cantidad_periodos' => 'required|integer',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            // 1. Desactivar la gestión activa actual (poner estado a Inactivo)
            \App\Models\Gestion::where('estado', 'Activo')->update(['estado' => 'Inactivo']);

            // 2. Crear la nueva gestión escolar como ACTIVA directamente
            \App\Models\Gestion::create([
                'nombre_gestion' => $request->nombre_gestion,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'tipo_periodo_academico' => $request->tipo_periodo_academico,
                'cantidad_periodos' => $request->cantidad_periodos,
                'estado' => 'Activo'
            ]);
        });

        return redirect()->back()->with('success', 'Nueva gestión escolar ofertada e iniciada como ACTIVA exitosamente.');
    }

    public function promocionar(Request $request)
    {
        $request->validate([
            'destino_gestion_id' => 'required|exists:gestiones,id',
            'origen_gestion_id' => 'required|exists:gestiones,id',
            'promociones' => 'required|array',
            'promociones.*.estudiante_id' => 'required|exists:estudiantes,id',
            'promociones.*.estado_anual' => 'required|in:Aprobado,Reprobado,Retirado',
            'promociones.*.curso_id' => 'nullable|exists:cursos,id',
            'promociones.*.curso_bth_id' => 'nullable|exists:cursos_bth,id',
            'promociones.*.motivo_retiro' => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $nuevaGestion = Gestion::find($request->destino_gestion_id);
            $origenGestion = Gestion::find($request->origen_gestion_id);

            foreach ($request->promociones as $promo) {
                $estudiante = \App\Models\Estudiante::find($promo['estudiante_id']);
                if (!$estudiante) {
                    continue;
                }

                // Si el estudiante ya es Bachiller o Graduado BTH, no permitir modificar su estado ni inscribirlo
                if ($estudiante->estado_global === 'Bachiller' || $estudiante->estado_global === 'Graduado BTH') {
                    continue;
                }

                // 1. Guardar el estado anual en el registro de la gestión de origen
                $registroActual = \App\Models\RegistroInternado::where('estudiante_id', $promo['estudiante_id'])
                    ->where('gestion_id', $origenGestion->id)
                    ->first();

                if ($registroActual) {
                    $registroActual->update([
                        'estado_anual' => $promo['estado_anual']
                    ]);
                }

                // 2. Si el estado es Retirado, actualizar estado global, guardar motivo y suspender cuenta
                if ($promo['estado_anual'] === 'Retirado') {
                    $estudiante->update([
                        'estado_global' => 'Retirado',
                        'motivo_retiro' => $promo['motivo_retiro'] ?? 'Retirado en proceso de promoción de gestión.'
                    ]);
                    
                    // Revocar acceso (suspender cuenta colocándolo en Pendiente)
                    \App\Models\User::where('persona_ci', $estudiante->persona_ci)->update([
                        'estado_cuenta' => 'Pendiente'
                    ]);
                    continue;
                }

                // 3. Si estaba en 6to de secundaria y aprobó, egresa Bachiller y no se inscribe
                $esSexto = false;
                if ($registroActual && $registroActual->curso) {
                    $nombreCurso = $registroActual->curso->nombre;
                    if (str_contains(strtolower($nombreCurso), '6to') || str_contains(strtolower($nombreCurso), 'sexto')) {
                        $esSexto = true;
                    }
                }

                if ($esSexto && $promo['estado_anual'] === 'Aprobado') {
                    $estudiante->update([
                        'estado_global' => 'Bachiller',
                        'año_egreso_bachiller' => $origenGestion->nombre_gestion
                    ]);
                    continue;
                }

                // 4. Inscribir al estudiante en la nueva gestión activa si no existe ya su registro
                $nuevoCursoId = $promo['curso_id'];
                $nuevoCursoBthId = $promo['curso_bth_id'];

                $pabellon = $registroActual ? $registroActual->pabellon : null;
                $cama = $registroActual ? $registroActual->cama : null;

                \App\Models\RegistroInternado::updateOrCreate(
                    [
                        'estudiante_id' => $estudiante->id,
                        'gestion_id' => $nuevaGestion->id
                    ],
                    [
                        'curso_id' => $nuevoCursoId,
                        'curso_bth_id' => $nuevoCursoBthId,
                        'pabellon' => $pabellon,
                        'cama' => $cama,
                        'estado_anual' => 'Cursando'
                    ]
                );

                // Reactivar el estado global del estudiante si correspondiese
                if ($estudiante->estado_global !== 'Graduado BTH' && $estudiante->estado_global !== 'Bachiller') {
                    $estudiante->update(['estado_global' => 'Activo']);
                }
            }
        });

        return redirect()->back()->with('success', 'Estudiantes seleccionados promocionados e inscritos exitosamente.');
    }
}
