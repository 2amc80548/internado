<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Persona;
use App\Models\Rol;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'ci' => 'required|string',
            'email' => 'nullable|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 1. Buscar si la persona existe y está registrada como estudiante
        $persona = Persona::where('ci', $request->ci)->first();
        if (!$persona || !$persona->estudiante) {
            throw ValidationException::withMessages([
                'ci' => 'El número de carnet no está registrado como estudiante en el internado. Por favor, comuníquese con la administración para su registro.',
            ]);
        }

        // 2. Verificar si ya existe una cuenta de usuario para este estudiante
        $userExists = User::where('persona_ci', $persona->ci)->exists();
        if ($userExists) {
            throw ValidationException::withMessages([
                'ci' => 'Este número de carnet ya tiene una cuenta registrada en el sistema.',
            ]);
        }

        $rol = Rol::where('nombre', 'Estudiante')->first();

        // 3. Crear el usuario enlazado a la persona existente
        $user = User::create([
            'name' => $persona->nombre . ' ' . $persona->ap_paterno,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'persona_ci' => $persona->ci,
            'rol_id' => $rol ? $rol->id : null,
            'estado_cuenta' => 'Pendiente'
        ]);

        event(new Registered($user));

        return redirect()->route('login')->with('status', 'Solicitud de cuenta enviada correctamente. Por favor, espere a que el administrador apruebe su cuenta.');
    }
}
