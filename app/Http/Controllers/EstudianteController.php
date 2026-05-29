<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Http\Requests\StoreEstudianteRequest;
use App\Http\Requests\UpdateEstudianteRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

use Illuminate\Support\Facades\Storage;

class EstudianteController extends Controller
{
    public function index()
    {
        $estudiantes = Estudiante::with(['persona', 'comunidad', 'tutor.persona'])->latest()->get();
        return Inertia::render('Estudiantes/Index', [
            'estudiantes' => $estudiantes
        ]);
    }

    public function create()
    {
        return Inertia::render('Estudiantes/Create');
    }

    public function store(StoreEstudianteRequest $request)
    {
        Estudiante::create($request->validated());

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante creado exitosamente.');
    }

    public function show(Estudiante $estudiante)
    {
        $estudiante->load(['persona', 'comunidad', 'tutor.persona', 'registrosInternado.gestion']);
        return Inertia::render('Estudiantes/Show', [
            'estudiante' => $estudiante
        ]);
    }

    public function edit(Estudiante $estudiante)
    {
        $estudiante->load(['persona']);
        return Inertia::render('Estudiantes/Edit', [
            'estudiante' => $estudiante
        ]);
    }

    public function update(UpdateEstudianteRequest $request, Estudiante $estudiante)
    {
        $estudiante->update($request->validated());

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante actualizado exitosamente.');
    }

    public function destroy(Estudiante $estudiante)
    {
        $estudiante->delete();

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante eliminado exitosamente.');
    }

    public function subirFoto(Request $request, Estudiante $estudiante)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            // Delete old photo if exists
            if ($estudiante->ruta_foto) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $estudiante->ruta_foto));
            }

            // Store new photo in 'fotos' directory under public disk
            $path = $request->file('foto')->store('fotos', 'public');
            
            // Save ruta_foto path
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
