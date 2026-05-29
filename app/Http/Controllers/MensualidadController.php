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
    public function index()
    {
        $mensualidades = Mensualidad::with(['registroInternado.estudiante.persona', 'registroInternado.gestion', 'registroInternado.curso'])->latest()->get();
        
        $gestionActual = Gestion::where('estado', 'Activo')->first();
        $estudiantesActivos = [];
        if ($gestionActual) {
            $estudiantesActivos = RegistroInternado::with('estudiante.persona')
                ->where('gestion_id', $gestionActual->id)
                ->where('estado_anual', 'Cursando')
                ->get();
        }

        $gestiones = Gestion::orderBy('nombre_gestion', 'desc')->get();

        return Inertia::render('Admin/Mensualidades/Index', [
            'mensualidades' => $mensualidades,
            'estudiantesActivos' => $estudiantesActivos,
            'gestiones' => $gestiones
        ]);
    }

    // Método para generación masiva (Admin)
    public function generarMasivo(Request $request)
    {
        $request->validate([
            'mes' => 'required|string|max:20',
            'monto' => 'required|numeric|min:0',
            'registro_internado_ids' => 'required|array',
            'registro_internado_ids.*' => 'exists:registros_internado,id'
        ]);

        $gestionActual = Gestion::where('estado', 'Activo')->first();
        if (!$gestionActual) {
            return redirect()->back()->withErrors(['gestion' => 'No hay una gestión activa.']);
        }

        $registros = RegistroInternado::whereIn('id', $request->registro_internado_ids)
                                      ->where('gestion_id', $gestionActual->id)
                                      ->get();

        DB::transaction(function () use ($registros, $request) {
            foreach ($registros as $registro) {
                // Verificar si ya tiene el mes generado
                $existe = Mensualidad::where('registro_internado_id', $registro->id)
                                     ->where('mes', $request->mes)
                                     ->exists();
                
                if (!$existe) {
                    Mensualidad::create([
                        'registro_internado_id' => $registro->id,
                        'mes' => $request->mes,
                        'monto' => $request->monto,
                        'total' => $request->monto,
                        'estado' => 'Pendiente'
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', 'Mensualidades generadas exitosamente para el mes de ' . $request->mes);
    }

    public function store(StoreMensualidadRequest $request)
    {
        // Los estudiantes ya no suben mensualidades, pero mantenemos el endpoint si es útil
        $data = $request->validated();
        Mensualidad::create($data);
        return redirect()->back()->with('success', 'Mensualidad registrada.');
    }

    public function update(Request $request, Mensualidad $mensualidad)
    {
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
        if ($mensualidad->estado === 'Pagado') {
            return redirect()->back()->withErrors([
                'delete' => 'Un pago registrado como PAGADO no se puede eliminar, solo se puede anular.'
            ]);
        }
        $mensualidad->delete();
        return redirect()->back()->with('success', 'Mensualidad eliminada exitosamente.');
    }
}
