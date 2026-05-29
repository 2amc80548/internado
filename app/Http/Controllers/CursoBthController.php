<?php

namespace App\Http\Controllers;

use App\Models\CursoBth;
use App\Models\CarreraTecnica;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CursoBthController extends Controller
{
    public function index()
    {
        $cursos = CursoBth::with('carreraTecnica')->latest()->get();
        $carreras = CarreraTecnica::all();
        
        return Inertia::render('Admin/CursosBth/Index', [
            'cursos' => $cursos,
            'carreras' => $carreras
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'carrera_tecnica_id' => 'required|exists:carreras_tecnicas,id',
            'nivel' => 'required|string|max:255'
        ]);

        CursoBth::create($request->all());

        return redirect()->back()->with('message', 'Curso BTH creado exitosamente.');
    }

    public function update(Request $request, CursoBth $cursos_bth) // Laravel pluraliza a cursos_bths pero el parámetro suele ser el singular de la ruta
    {
        $request->validate([
            'carrera_tecnica_id' => 'required|exists:carreras_tecnicas,id',
            'nivel' => 'required|string|max:255'
        ]);

        $cursos_bth->update($request->all());

        return redirect()->back()->with('message', 'Curso BTH actualizado.');
    }

    public function destroy(CursoBth $cursos_bth)
    {
        $cursos_bth->delete();
        return redirect()->back()->with('message', 'Curso BTH eliminado.');
    }
}
