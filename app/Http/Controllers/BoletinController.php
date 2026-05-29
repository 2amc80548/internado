<?php

namespace App\Http\Controllers;

use App\Models\BoletinCalificacion;
use App\Http\Requests\StoreBoletinRequest;
use App\Http\Requests\UpdateBoletinRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BoletinController extends Controller
{
    public function index()
    {
        $boletines = BoletinCalificacion::with('registroInternado.estudiante.persona')->latest()->get();
        return Inertia::render('Boletines/Index', [
            'boletines' => $boletines
        ]);
    }

    public function create()
    {
        return Inertia::render('Boletines/Create');
    }

    public function store(StoreBoletinRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('ruta_archivo')) {
            $data['ruta_archivo'] = $request->file('ruta_archivo')->store('boletines', 'public');
        }

        BoletinCalificacion::create($data);

        return redirect()->route('boletines.index')->with('success', 'Boletín subido exitosamente.');
    }

    public function show(BoletinCalificacion $boletin)
    {
        $boletin->load('registroInternado.estudiante.persona');
        return Inertia::render('Boletines/Show', [
            'boletin' => $boletin
        ]);
    }

    public function edit(BoletinCalificacion $boletin)
    {
        return Inertia::render('Boletines/Edit', [
            'boletin' => $boletin
        ]);
    }

    public function update(UpdateBoletinRequest $request, BoletinCalificacion $boletin)
    {
        $data = $request->validated();

        if ($request->hasFile('ruta_archivo')) {
            if ($boletin->ruta_archivo) {
                Storage::disk('public')->delete($boletin->ruta_archivo);
            }
            $data['ruta_archivo'] = $request->file('ruta_archivo')->store('boletines', 'public');
        }

        $boletin->update($data);

        return redirect()->route('boletines.index')->with('success', 'Boletín actualizado exitosamente.');
    }

    public function destroy(BoletinCalificacion $boletin)
    {
        if ($boletin->ruta_archivo) {
            Storage::disk('public')->delete($boletin->ruta_archivo);
        }
        
        $boletin->delete();

        return redirect()->route('boletines.index')->with('success', 'Boletín eliminado exitosamente.');
    }
}
