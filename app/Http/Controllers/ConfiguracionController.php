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
            'ruta_logo' => 'nullable|image|max:5120',
            'ruta_logo_login' => 'nullable|image|max:5120',
            'nombre_sistema' => 'required|string|max:100',
            'whatsapp_notificaciones' => 'nullable|string|max:30',
            'color_hexadecimal' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'permisos_encargada' => 'nullable|array',
            'modo_mensualidad_automatica' => 'boolean',
            'edicion_perfil_habilitada' => 'boolean',
        ]);

        if ($request->hasFile('ruta_qr_pagos')) {
            // Delete old QR if exists
            if ($config->ruta_qr_pagos) {
                Storage::disk('public')->delete($config->ruta_qr_pagos);
            }
            $config->ruta_qr_pagos = $request->file('ruta_qr_pagos')->store('qrs', 'public');
        }

        if ($request->hasFile('ruta_logo')) {
            // Delete old system logo if exists
            if ($config->ruta_logo) {
                Storage::disk('public')->delete($config->ruta_logo);
            }
            $config->ruta_logo = $request->file('ruta_logo')->store('logos', 'public');
        }

        if ($request->hasFile('ruta_logo_login')) {
            // Delete old login logo if exists
            if ($config->ruta_logo_login) {
                Storage::disk('public')->delete($config->ruta_logo_login);
            }
            $config->ruta_logo_login = $request->file('ruta_logo_login')->store('logos', 'public');
        }

        $config->nombre_sistema = $request->input('nombre_sistema', 'INTERNADO');
        $config->whatsapp_notificaciones = $request->input('whatsapp_notificaciones');
        $config->color_hexadecimal = $request->input('color_hexadecimal', '#0d9488');
        $config->permisos_encargada = $request->input('permisos_encargada', []);

        $config->modo_mensualidad_automatica = $request->boolean('modo_mensualidad_automatica');
        $config->edicion_perfil_habilitada = $request->boolean('edicion_perfil_habilitada');

        $config->save();

        return redirect()->back()->with('message', 'Configuración actualizada exitosamente.');
    }
}
