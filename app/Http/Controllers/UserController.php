<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Provincia;
use App\Models\Comunidad;
use App\Models\Curso;
use App\Models\CursoBth;
use App\Models\Tutor;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\RegistroInternado;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with([
            'persona.estudiante', 
            'rol'
        ])->get();

        $roles = Rol::all();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ci' => 'required|string|unique:personas,ci',
            'nombre' => 'required|string',
            'ap_paterno' => 'required|string',
            'ap_materno' => 'nullable|string',
            'rol_id' => 'required|exists:roles,id',
            'email' => 'nullable|email|unique:users,email',
            'celular' => 'nullable|string',
            'sexo' => 'nullable|string|in:M,F',
        ]);

        DB::transaction(function () use ($request) {
            $persona = Persona::create([
                'ci' => $request->ci,
                'nombre' => $request->nombre,
                'ap_paterno' => $request->ap_paterno,
                'ap_materno' => $request->ap_materno,
                'celular' => $request->celular,
                'sexo' => $request->sexo,
                'estado' => true
            ]);

            User::create([
                'name' => $request->nombre . ' ' . $request->ap_paterno,
                'email' => $request->email,
                'password' => Hash::make($request->ci),
                'persona_ci' => $persona->ci,
                'rol_id' => $request->rol_id,
                'estado_cuenta' => 'Aprobado'
            ]);
        });

        return redirect()->back()->with('success', 'Usuario creado exitosamente.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'nombre' => 'required|string',
            'ap_paterno' => 'required|string',
            'ap_materno' => 'nullable|string',
            'rol_id' => 'required|exists:roles,id',
            'email' => 'nullable|email|unique:users,email,'.$user->id,
            'celular' => 'nullable|string',
            'sexo' => 'nullable|string|in:M,F',
        ]);

        DB::transaction(function () use ($request, $user) {
            $user->persona->update([
                'nombre' => $request->nombre,
                'ap_paterno' => $request->ap_paterno,
                'ap_materno' => $request->ap_materno,
                'celular' => $request->celular,
                'sexo' => $request->sexo,
            ]);

            $user->update([
                'name' => $request->nombre . ' ' . $request->ap_paterno,
                'email' => $request->email,
                'rol_id' => $request->rol_id
            ]);

            if ($user->estado_cuenta === 'Pendiente') {
                $user->update(['estado_cuenta' => 'Aprobado']);
            }
        });

        return redirect()->back()->with('success', 'Usuario actualizado exitosamente.');
    }

    public function aprobarCuenta(User $user)
    {
        $user->update(['estado_cuenta' => 'Aprobado']);
        return redirect()->back()->with('success', 'Cuenta de usuario aprobada exitosamente.');
    }

    public function aprobarCuentasMasivo(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        User::whereIn('id', $request->user_ids)->update(['estado_cuenta' => 'Aprobado']);

        return redirect()->back()->with('success', 'Cuentas seleccionadas aprobadas exitosamente.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return redirect()->back()->with('success', 'Contraseña actualizada exitosamente.');
    }

    public function destroy(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, auth()->user()->password)) {
            return redirect()->back()->withErrors([
                'delete_password' => 'La contraseña de confirmación es incorrecta.'
            ]);
        }

        DB::transaction(function () use ($user) {
            $persona = $user->persona;
            $user->delete();
            
            if ($persona && !$persona->estudiante) {
                $persona->delete();
            }
        });

        return redirect()->back()->with('success', 'Usuario de acceso eliminado exitosamente.');
    }
}
