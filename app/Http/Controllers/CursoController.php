<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CursoController extends Controller
{
    public function index()
    {
        $cursos = Curso::withCount('registrosInternado')->get();
        return Inertia::render('Admin/Cursos/Index', [
            'cursos' => $cursos
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|string|unique:cursos,nombre']);
        Curso::create($request->all());
        return redirect()->back()->with('success', 'Curso creado exitosamente.');
    }

    public function update(Request $request, Curso $curso)
    {
        $request->validate(['nombre' => 'required|string|unique:cursos,nombre,'.$curso->id]);
        $curso->update($request->all());
        return redirect()->back()->with('success', 'Curso actualizado exitosamente.');
    }

    public function destroy(Curso $curso)
    {
        $curso->delete();
        return redirect()->back()->with('success', 'Curso eliminado exitosamente.');
    }
}
