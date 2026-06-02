<?php

namespace App\Http\Controllers;

use App\Models\Mensualidad;
use App\Models\Gestion;
use App\Models\RegistroInternado;
use App\Http\Requests\StoreMensualidadRequest;
use App\Http\Requests\UpdateMensualidadRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class MensualidadController extends Controller
{
    public function index(Request $request)
    {
        $this->checkPermission('mensualidades.index');

        $search = $request->query('search');
        $estado = $request->query('estado');
        $gestionId = $request->query('gestion_id');
        $cargarTodos = $request->boolean('cargar_todos') || $request->query('cargar_todos') === '1';

        $hasFilters = $search || $estado || $gestionId || $cargarTodos;

        $gestionActual = Gestion::where('estado', 'Activo')->first();
        
        $estudiantesActivos = [];
        if ($gestionActual) {
            $estudiantesActivos = RegistroInternado::with('estudiante.persona')
                ->where('gestion_id', $gestionActual->id)
                ->where('estado_anual', 'Cursando')
                ->get();
        }

        $gestiones = Gestion::orderBy('nombre_gestion', 'desc')->get();

        if ($hasFilters) {
            $query = Mensualidad::with([
                'registroInternado.estudiante.persona', 
                'registroInternado.gestion', 
                'registroInternado.curso'
            ]);

            if ($search) {
                $query->whereHas('registroInternado.estudiante.persona', function($q) use ($search) {
                    $q->where('nombre', 'ilike', "%{$search}%")
                      ->orWhere('ap_paterno', 'ilike', "%{$search}%")
                      ->orWhere('ap_materno', 'ilike', "%{$search}%")
                      ->orWhere('ci', 'like', "%{$search}%");
                });
            }

            if ($estado) {
                if ($estado === 'Pendiente') {
                    $query->whereIn('estado', ['Pendiente', 'Pendiente_Verificacion']);
                } else {
                    $query->where('estado', $estado);
                }
            }

            if ($gestionId) {
                $query->whereHas('registroInternado', function($q) use ($gestionId) {
                    $q->where('gestion_id', $gestionId);
                });
            }

            $mensualidades = $query->latest()->get();
        } else {
            $mensualidades = [];
        }

        return Inertia::render('Admin/Mensualidades/Index', [
            'mensualidades' => $mensualidades,
            'estudiantesActivos' => $estudiantesActivos,
            'gestiones' => $gestiones
        ]);
    }

    // Método para generación masiva (Admin)
    public function generarMasivo(Request $request)
    {
        $this->checkPermission('mensualidades.index');
        $request->validate([
            'concepto_tipo' => 'required|string|in:mes,personalizado',
            'mes' => 'nullable|string|max:100',
            'concepto_personalizado' => 'nullable|string|max:100',
            'monto' => 'required|numeric|min:0',
            'registro_internado_ids' => 'required|array',
            'registro_internado_ids.*' => 'exists:registros_internado,id'
        ]);

        $gestionActual = Gestion::where('estado', 'Activo')->first();
        if (!$gestionActual) {
            return redirect()->back()->withErrors(['gestion' => 'No hay una gestión activa.']);
        }

        // Determinar el concepto final de la deuda
        $finalConcept = '';
        if ($request->concepto_tipo === 'mes') {
            $mesesValidos = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            $mes = $request->mes;
            if (in_array($mes, $mesesValidos)) {
                $finalConcept = 'Mensualidad de ' . $mes;
            } else {
                $finalConcept = $mes;
            }
        } else {
            $finalConcept = trim($request->concepto_personalizado);
        }

        if (empty($finalConcept)) {
            return redirect()->back()->withErrors(['mes' => 'El concepto o mes de la deuda no puede estar vacío.']);
        }

        $registros = RegistroInternado::with('estudiante.persona')
                                      ->whereIn('id', $request->registro_internado_ids)
                                      ->where('gestion_id', $gestionActual->id)
                                      ->get();

        $duplicados = [];
        $generados = 0;

        DB::transaction(function () use ($registros, $finalConcept, $request, &$generados, &$duplicados) {
            foreach ($registros as $registro) {
                // Verificar si ya tiene la misma deuda generada
                $existe = Mensualidad::where('registro_internado_id', $registro->id)
                                     ->where('mes', $finalConcept)
                                     ->exists();
                
                if ($existe) {
                    $nombreEstudiante = $registro->estudiante->persona->nombre . ' ' . $registro->estudiante->persona->ap_paterno;
                    $duplicados[] = $nombreEstudiante . ' (CI: ' . $registro->estudiante->persona->ci . ')';
                } else {
                    Mensualidad::create([
                        'registro_internado_id' => $registro->id,
                        'mes' => $finalConcept,
                        'monto' => $request->monto,
                        'total' => $request->monto,
                        'estado' => 'Pendiente'
                    ]);
                    $generados++;
                }
            }
        });

        if ($generados === 0 && count($duplicados) > 0) {
            return redirect()->back()->withErrors([
                'mes' => 'La deuda "' . $finalConcept . '" ya ha sido generada anteriormente para todos los estudiantes seleccionados.'
            ]);
        }

        $msg = "Se han generado {$generados} deudas de \"{$finalConcept}\" exitosamente.";
        if (count($duplicados) > 0) {
            $msg .= " Se omitieron " . count($duplicados) . " estudiante(s) por ya contar con esta deuda registrada: " . implode(', ', $duplicados);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function store(StoreMensualidadRequest $request)
    {
        $this->checkPermission('mensualidades.index');
        // Los estudiantes ya no suben mensualidades, pero mantenemos el endpoint si es útil
        $data = $request->validated();
        Mensualidad::create($data);
        return redirect()->back()->with('success', 'Mensualidad registrada.');
    }

    public function update(Request $request, Mensualidad $mensualidad)
    {
        $this->checkPermission('mensualidades.index');
        $request->validate([
            'estado' => 'required|string|in:Pendiente,Pagado,Anulado',
            'metodo_pago' => 'nullable|required_if:estado,Pagado|string|in:Efectivo,QR',
            'fecha_pago' => 'nullable|required_if:estado,Pagado|date',
            'motivo_anulacion' => 'nullable|required_if:estado,Anulado|string',
            'password' => 'nullable|required_if:estado,Anulado|string',
        ]);

        if ($request->estado === 'Anulado') {
            // Verificar la contraseña de administrador
            if (!$request->password || !\Illuminate\Support\Facades\Hash::check($request->password, auth()->user()->password)) {
                return redirect()->back()->withErrors([
                    'password' => 'La contraseña de confirmación es incorrecta.'
                ]);
            }

            // Un pago pagado no se puede eliminar, solo se puede anular ingresando un motivo
            $mensualidad->update([
                'estado' => 'Anulado',
                'motivo_anulacion' => $request->motivo_anulacion
            ]);

            return redirect()->back()->with('success', 'Pago anulado exitosamente.');
        }

        $mensualidad->update([
            'estado' => $request->estado,
            'metodo_pago' => $request->estado === 'Pagado' ? $request->metodo_pago : null,
            'fecha_pago' => $request->estado === 'Pagado' ? ($request->fecha_pago ?? now()->toDateString()) : null,
        ]);

        return redirect()->back()->with('success', 'Estado de la mensualidad actualizado.');
    }

    public function destroy(Mensualidad $mensualidad)
    {
        $this->checkPermission('mensualidades.index');
        if ($mensualidad->estado === 'Pagado') {
            return redirect()->back()->withErrors([
                'delete' => 'Un pago registrado como PAGADO no se puede eliminar, solo se puede anular.'
            ]);
        }
        $mensualidad->delete();
        return redirect()->back()->with('success', 'Mensualidad eliminada exitosamente.');
    }
}
