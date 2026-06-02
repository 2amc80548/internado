<?php

namespace App\Http\Controllers;

use App\Models\IncidenciaHistorial;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\RegistroInternado;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IncidenciaController extends Controller
{
    public function index(Request $request)
    {
        $this->checkPermission('incidencias.index');

        $search = $request->query('search');
        $estudiantes = [];

        $gestionActual = Gestion::where('estado', 'Activo')->first();

        if ($search && $gestionActual) {
            $estudiantes = Estudiante::with([
                'persona',
                'registrosInternado' => function($q) use ($gestionActual) {
                    $q->where('gestion_id', $gestionActual->id)
                      ->with(['curso', 'incidencias' => function($qi) {
                          $qi->orderBy('fecha', 'desc');
                      }]);
                }
            ])
            ->whereHas('persona', function($q) use ($search) {
                $q->where('nombre', 'ilike', "%{$search}%")
                  ->orWhere('ap_paterno', 'ilike', "%{$search}%")
                  ->orWhere('ap_materno', 'ilike', "%{$search}%")
                  ->orWhere('ci', 'like', "%{$search}%");
            })
            ->take(15)
            ->get();
        }

        return Inertia::render('Admin/Incidencias/Index', [
            'estudiantes' => $estudiantes,
            'searchQuery' => $search ?? '',
            'gestionActual' => $gestionActual
        ]);
    }

    public function store(Request $request)
    {
        $this->checkPermission('incidencias.index');

        $request->validate([
            'registro_internado_id' => 'required|exists:registros_internado,id',
            'fecha' => 'required|date',
            'tipo_falta' => 'required|string|in:Leve,Grave,Muy Grave',
            'descripcion' => 'required|string',
            'sancion' => 'nullable|string',
            'estado_sancion' => 'required|string|in:Pendiente,Cumplida,Cancelada'
        ]);

        IncidenciaHistorial::create($request->all());

        return redirect()->back()->with('success', 'Incidencia disciplinaria registrada con éxito.');
    }

    public function update(Request $request, IncidenciaHistorial $incidencia)
    {
        $this->checkPermission('incidencias.index');

        $request->validate([
            'fecha' => 'required|date',
            'tipo_falta' => 'required|string|in:Leve,Grave,Muy Grave',
            'descripcion' => 'required|string',
            'sancion' => 'nullable|string',
            'estado_sancion' => 'required|string|in:Pendiente,Cumplida,Cancelada'
        ]);

        $incidencia->update($request->all());

        return redirect()->back()->with('success', 'Incidencia disciplinaria actualizada con éxito.');
    }

    public function destroy(IncidenciaHistorial $incidencia)
    {
        $this->checkPermission('incidencias.index');

        $incidencia->delete();

        return redirect()->back()->with('success', 'Incidencia disciplinaria eliminada con éxito.');
    }
}
