<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'ci' => 'required|string|unique:personas,ci',
            'nombre' => 'required|string|max:255',
            'ap_paterno' => 'required|string|max:255',
            'ap_materno' => 'nullable|string|max:255',
            'celular' => 'nullable|string|max:20',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $persona = \App\Models\Persona::create([
                'ci' => $request->ci,
                'nombre' => $request->nombre,
                'ap_paterno' => $request->ap_paterno,
                'ap_materno' => $request->ap_materno,
                'celular' => $request->celular,
                'estado' => true
            ]);

            $rol = \App\Models\Rol::where('nombre', 'Estudiante')->first();

            $user = User::create([
                'name' => $request->nombre . ' ' . $request->ap_paterno,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'persona_ci' => $persona->ci,
                'rol_id' => $rol ? $rol->id : null,
                'estado_cuenta' => 'Pendiente'
            ]);

            $path = null;
            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store('fotos_perfil', 'public');
            }

            \App\Models\Estudiante::create([
                'persona_ci' => $persona->ci,
                'ruta_foto' => $path,
            ]);

            event(new Registered($user));
        });

        return redirect()->route('login')->with('status', 'Solicitud enviada correctamente. Por favor, espere a que el administrador apruebe su cuenta.');
    }
}
