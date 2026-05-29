<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Curso;
use App\Models\RegistroInternado;

class AdminBoletinController extends Controller
{
    public function index()
    {
        $cursos = Curso::all();
        
        $registros = RegistroInternado::with([
            'estudiante.persona', 
            'curso', 
            'gestion', 
            'boletines'
        ])->get();

        return Inertia::render('Admin/Boletines/Index', [
            'cursos' => $cursos,
            'registros' => $registros
        ]);
    }
}
