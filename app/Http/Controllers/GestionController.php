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

    public function update(UpdateGestionRequest $request, Gestion $gestion)
    {
        $gestion->update($request->validated());

        return redirect()->route('gestiones.index')->with('success', 'Gestión actualizada exitosamente.');
    }

    public function destroy(Gestion $gestion)
    {
        $gestion->delete();

        return redirect()->route('gestiones.index')->with('success', 'Gestión eliminada exitosamente.');
    }

    public function promocionar(Request $request)
    {
        $request->validate([
            'nombre_gestion' => 'required|string|unique:gestiones,nombre_gestion',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'tipo_periodo_academico' => 'required|string',
            'cantidad_periodos' => 'required|integer',
            'promociones' => 'required|array',
            'promociones.*.estudiante_id' => 'required|exists:estudiantes,id',
            'promociones.*.estado_anual' => 'required|in:Aprobado,Reprobado,Retirado',
            'promociones.*.curso_id' => 'nullable|exists:cursos,id',
            'promociones.*.curso_bth_id' => 'nullable|exists:cursos_bth,id',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            // 1. Obtener la gestión activa actual
            $gestionActual = Gestion::where('estado', 'Activo')->first();

            // 2. Si hay una gestión activa actual, guardar los estados anuales en los registros actuales
            if ($gestionActual) {
                foreach ($request->promociones as $promo) {
                    $registroActual = \App\Models\RegistroInternado::where('estudiante_id', $promo['estudiante_id'])
                        ->where('gestion_id', $gestionActual->id)
                        ->first();

                    if ($registroActual) {
                        $registroActual->update([
                            'estado_anual' => $promo['estado_anual']
                        ]);
                    }

                    // Si el estudiante fue retirado, actualizar su estado global
                    if ($promo['estado_anual'] === 'Retirado') {
                        $estudiante = \App\Models\Estudiante::find($promo['estudiante_id']);
                        if ($estudiante) {
                            $estudiante->update(['estado_global' => 'Retirado']);
                        }
                    }
                }

                // Desactivar la gestión actual
                $gestionActual->update(['estado' => 'Inactivo']);
            }

            // 3. Crear la nueva gestión activa
            $nuevaGestion = Gestion::create([
                'nombre_gestion' => $request->nombre_gestion,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'tipo_periodo_academico' => $request->tipo_periodo_academico,
                'cantidad_periodos' => $request->cantidad_periodos,
                'estado' => 'Activo'
            ]);

            // 4. Inscribir a los estudiantes aprobados o reprobados en la nueva gestión
            foreach ($request->promociones as $promo) {
                if ($promo['estado_anual'] === 'Retirado') {
                    continue; // Los retirados no se vuelven a inscribir
                }

                $estudiante = \App\Models\Estudiante::find($promo['estudiante_id']);
                if (!$estudiante) {
                    continue;
                }

                // Obtener el registro anterior para verificar el curso y cama
                $registroAnterior = null;
                $esSexto = false;
                if ($gestionActual) {
                    $registroAnterior = \App\Models\RegistroInternado::where('estudiante_id', $estudiante->id)
                        ->where('gestion_id', $gestionActual->id)
                        ->with('curso')
                        ->first();
                    if ($registroAnterior && $registroAnterior->curso) {
                        $nombreCurso = $registroAnterior->curso->nombre;
                        if (str_contains(strtolower($nombreCurso), '6to') || str_contains(strtolower($nombreCurso), 'sexto')) {
                            $esSexto = true;
                        }
                    }
                }

                // Si estaba en 6to de secundaria y aprobó, pasa automáticamente a Bachiller y no se inscribe
                if ($esSexto && $promo['estado_anual'] === 'Aprobado') {
                    $estudiante->update([
                        'estado_global' => 'Bachiller',
                        'año_egreso_bachiller' => $gestionActual ? $gestionActual->nombre_gestion : date('Y')
                    ]);
                    continue; // No se vuelve a inscribir ya que culminó el colegio
                }

                // Registrar en la nueva gestión
                $nuevoCursoId = $promo['curso_id'];
                $nuevoCursoBthId = $promo['curso_bth_id'];

                // Mantener el mismo pabellón y cama de su registro anterior (si existía)
                $pabellon = null;
                $cama = null;
                if ($registroAnterior) {
                    $pabellon = $registroAnterior->pabellon;
                    $cama = $registroAnterior->cama;
                }

                \App\Models\RegistroInternado::create([
                    'estudiante_id' => $estudiante->id,
                    'gestion_id' => $nuevaGestion->id,
                    'curso_id' => $nuevoCursoId,
                    'curso_bth_id' => $nuevoCursoBthId,
                    'pabellon' => $pabellon,
                    'cama' => $cama,
                    'estado_anual' => 'Cursando'
                ]);

                // Asegurar que el estudiante sigue estando activo globalmente (si no es Graduado BTH)
                if ($estudiante->estado_global !== 'Graduado BTH') {
                    $estudiante->update(['estado_global' => 'Activo']);
                }
            }
        });

        return redirect()->back()->with('success', 'Gestión promocionada y estudiantes inscritos exitosamente en el nuevo ciclo.');
    }
}
