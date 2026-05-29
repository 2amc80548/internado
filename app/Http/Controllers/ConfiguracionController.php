<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionSistema;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $configuracion = ConfiguracionSistema::first() ?? new ConfiguracionSistema();
        
        return Inertia::render('Admin/Configuracion/Index', [
            'configuracion' => $configuracion
        ]);
    }

    public function update(Request $request)
    {
        $config = ConfiguracionSistema::first() ?? new ConfiguracionSistema();
        
        $request->validate([
            'ruta_qr_pagos' => 'nullable|image|max:5120', // Max 5MB
            'modo_mensualidad_automatica' => 'boolean',
            'edicion_perfil_habilitada' => 'boolean',
        ]);

        if ($request->hasFile('ruta_qr_pagos')) {
            // Delete old QR if exists
            if ($config->ruta_qr_pagos) {
                Storage::disk('public')->delete($config->ruta_qr_pagos);
            }
            
            $path = $request->file('ruta_qr_pagos')->store('qrs', 'public');
            $config->ruta_qr_pagos = $path;
        }

        $config->modo_mensualidad_automatica = $request->boolean('modo_mensualidad_automatica');
        $config->edicion_perfil_habilitada = $request->boolean('edicion_perfil_habilitada');

        $config->save();

        return redirect()->back()->with('message', 'Configuración actualizada exitosamente.');
    }
}
