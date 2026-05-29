<?php

namespace App\Http\Controllers;

use App\Models\Comunidad;
use App\Models\Provincia;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ComunidadController extends Controller
{
    public function index()
    {
        $comunidades = Comunidad::with('provincia')->latest()->get();
        $provincias = Provincia::all();
        
        return Inertia::render('Admin/Comunidades/Index', [
            'comunidades' => $comunidades,
            'provincias' => $provincias
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'provincia_id' => 'required|exists:provincias,id'
        ]);

        Comunidad::create($request->all());

        return redirect()->back()->with('message', 'Comunidad creada exitosamente.');
    }

    public function update(Request $request, Comunidad $comunidade)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'provincia_id' => 'required|exists:provincias,id'
        ]);

        $comunidade->update($request->all());

        return redirect()->back()->with('message', 'Comunidad actualizada.');
    }

    public function destroy(Comunidad $comunidade)
    {
        $comunidade->delete();
        return redirect()->back()->with('message', 'Comunidad eliminada.');
    }
}
