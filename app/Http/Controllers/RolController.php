<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::with('permisos')->get();
        $permisos = Permiso::all();
        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'permisos' => $permisos
        ]);
    }
}
