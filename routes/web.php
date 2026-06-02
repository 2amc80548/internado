<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $rol = auth()->user()->rol->nombre ?? '';
    if ($rol === 'Superadmin') {
        return redirect()->route('superadmin.dashboard');
    } elseif ($rol === 'Encargada') {
        return redirect()->route('encargada.dashboard');
    } elseif ($rol === 'Estudiante') {
        return redirect()->route('estudiante.portal');
    }
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

use App\Http\Controllers\GestionController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\MensualidadController;
use App\Http\Controllers\BoletinController;

use App\Models\Estudiante;
use App\Models\Mensualidad;
use App\Models\RegistroInternado;

use App\Http\Controllers\UserController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\AdminBoletinController;
use App\Http\Controllers\ComunidadController;
use App\Http\Controllers\CursoBthController;
use App\Http\Controllers\ConfiguracionController;

Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);
    Route::put('/users/{user}/aprobar', [UserController::class, 'aprobarCuenta'])->name('users.aprobar');
    Route::put('/users/{user}/rechazar', [UserController::class, 'rechazarCuenta'])->name('users.rechazar');
    Route::post('/users/aprobar-masivo', [UserController::class, 'aprobarCuentasMasivo'])->name('users.aprobar.masivo');
    Route::post('/users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
    Route::resource('cursos', CursoController::class);
    Route::resource('roles', RolController::class);
    Route::resource('comunidades', ComunidadController::class)->parameters(['comunidades' => 'comunidad']);
    Route::resource('cursos-bth', CursoBthController::class);
    
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');

    Route::post('/mensualidades/generar-masivo', [MensualidadController::class, 'generarMasivo'])->name('mensualidades.generar_masivo');
    
    Route::get('/boletines-control', [AdminBoletinController::class, 'index'])->name('boletines.control');

    Route::get('/superadmin/dashboard', function () {
        $activeStudents = Estudiante::where('estado_global', 'Activo')->count();
        $bachilleresCount = Estudiante::where('estado_global', 'Bachiller')->count();
        $graduadosBthCount = Estudiante::where('estado_global', 'Graduado BTH')->count();
        $pendingAccounts = \App\Models\User::where('estado_cuenta', 'Pendiente')->count();
        
        $montoTotalRecaudado = (float)\App\Models\Mensualidad::where('estado', 'Pagado')->sum('monto');
        $pendingPayments = \App\Models\Mensualidad::whereIn('estado', ['Pendiente', 'Pendiente_Verificacion'])->count();
        $totalPayments = \App\Models\Mensualidad::count();
        $paidPayments = \App\Models\Mensualidad::where('estado', 'Pagado')->count();
        
        $porcentajeCobro = $totalPayments > 0 ? round(($paidPayments / $totalPayments) * 100) : 0;
        
        $recentInscriptions = RegistroInternado::with(['estudiante.persona', 'gestion'])->latest()->take(5)->get();
        $recentPayments = Mensualidad::with(['registroInternado.estudiante.persona'])->where('estado', 'Pendiente_Verificacion')->latest()->take(5)->get();

        // Recaudación mensual para gráfica/indicador del año actual (Base de datos agnóstico y seguro)
        $currentYear = date('Y');
        $currentYearPaid = \App\Models\Mensualidad::where('estado', 'Pagado')
            ->whereYear('fecha_pago', $currentYear)
            ->get();
        
        $mesesNombres = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];
        
        $recaudacionMensual = [];
        foreach ($mesesNombres as $num => $nom) {
            $recaudacionMensual[] = [
                'mes' => $nom,
                'total' => 0
            ];
        }
        
        foreach ($currentYearPaid as $mnd) {
            if ($mnd->fecha_pago) {
                $monthNum = (int)date('n', strtotime($mnd->fecha_pago));
                if ($monthNum >= 1 && $monthNum <= 12) {
                    $recaudacionMensual[$monthNum - 1]['total'] += (float)$mnd->monto;
                }
            }
        }

        return Inertia::render('Admin/Dashboard', [
            'role' => 'Superadmin',
            'metrics' => [
                'activeStudents' => $activeStudents,
                'bachilleresCount' => $bachilleresCount,
                'graduadosBthCount' => $graduadosBthCount,
                'pendingAccounts' => $pendingAccounts,
                'montoTotalRecaudado' => $montoTotalRecaudado,
                'pendingPayments' => $pendingPayments,
                'totalPayments' => $totalPayments,
                'paidPayments' => $paidPayments,
                'porcentajeCobro' => $porcentajeCobro,
                'recaudacionMensual' => $recaudacionMensual
            ],
            'recentInscriptions' => $recentInscriptions,
            'recentPayments' => $recentPayments,
        ]);
    })->name('superadmin.dashboard');

    Route::get('/encargada/dashboard', function () {
        return Inertia::render('Dashboard', ['role' => 'Encargada']);
    })->name('encargada.dashboard');

    Route::get('/estudiante/portal', function () {
        $user = auth()->user();
        
        // Obtener el estudiante con todas sus relaciones clave
        $estudiante = Estudiante::with([
            'persona',
            'comunidad',
            'tutor.persona',
            'registrosInternado' => function($q) {
                $q->latest()->with(['gestion', 'boletines', 'mensualidades', 'incidencias', 'curso', 'cursoBth.carreraTecnica']);
            }
        ])->where('persona_ci', $user->persona_ci)->first();

        // Verificar si tiene un registro activo en alguna gestión
        $registroActual = $estudiante ? $estudiante->registrosInternado->first() : null;
        $configuracion = \App\Models\ConfiguracionSistema::first();

        return Inertia::render('Student/Portal', [
            'role' => 'Estudiante',
            'estudiante' => $estudiante,
            'registroActual' => $registroActual,
            'configuracion' => $configuracion,
        ]);
    })->name('estudiante.portal');

    Route::post('/estudiantes/{estudiante}/foto', [\App\Http\Controllers\EstudianteController::class, 'subirFoto'])->name('estudiantes.foto');

    Route::post('/gestiones/ofertar', [GestionController::class, 'ofertar'])->name('gestiones.ofertar');
    Route::post('/gestiones/promocionar', [GestionController::class, 'promocionar'])->name('gestiones.promocionar');
    Route::resource('gestiones', GestionController::class)->parameters(['gestiones' => 'gestion']);
    Route::resource('estudiantes', EstudianteController::class);
    Route::resource('mensualidades', MensualidadController::class)->parameters(['mensualidades' => 'mensualidad']);
    Route::resource('boletines', BoletinController::class)->parameters(['boletines' => 'boletin']);
    Route::resource('incidencias', \App\Http\Controllers\IncidenciaController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
