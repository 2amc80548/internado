<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function checkPermission(string $permission)
    {
        if (auth()->user()->rol?->nombre === 'Encargada') {
            $config = \App\Models\ConfiguracionSistema::first();
            $permisos = $config ? ($config->permisos_encargada ?? []) : [];
            if (!in_array($permission, $permisos)) {
                abort(403, 'No tiene permiso para acceder a esta sección.');
            }
        }
    }
}
