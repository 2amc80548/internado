<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\MensualidadController;
use App\Http\Controllers\BoletinController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\AdminBoletinController;
use App\Http\Controllers\ComunidadController;
use App\Http\Controllers\CursoBthController;
use App\Http\Controllers\ConfiguracionController;
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

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

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

    Route::get('/superadmin/dashboard', [DashboardController::class, 'superadmin'])->name('superadmin.dashboard');
    Route::get('/encargada/dashboard', [DashboardController::class, 'encargada'])->name('encargada.dashboard');
    Route::get('/estudiante/portal', [DashboardController::class, 'estudiante'])->name('estudiante.portal');

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
