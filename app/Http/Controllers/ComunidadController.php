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
        $this->checkPermission('comunidades.index');
        $comunidades = Comunidad::with('provincia')->latest()->get();
        $provincias = Provincia::all();
        
        return Inertia::render('Admin/Comunidades/Index', [
            'comunidades' => $comunidades,
            'provincias' => $provincias
        ]);
    }

    public function store(Request $request)
    {
        $this->checkPermission('comunidades.index');
        $request->validate([
            'nombre' => 'required|string|max:255',
            'provincia_id' => 'required|exists:provincias,id'
        ]);

        Comunidad::create($request->all());

        return redirect()->back()->with('message', 'Comunidad creada exitosamente.');
    }

    public function update(Request $request, Comunidad $comunidade)
    {
        $this->checkPermission('comunidades.index');
        $request->validate([
            'nombre' => 'required|string|max:255',
            'provincia_id' => 'required|exists:provincias,id'
        ]);

        $comunidade->update($request->all());

        return redirect()->back()->with('message', 'Comunidad actualizada.');
    }

    public function destroy(Comunidad $comunidade)
    {
        $this->checkPermission('comunidades.index');
        $comunidade->delete();
        return redirect()->back()->with('message', 'Comunidad eliminada.');
    }
}
