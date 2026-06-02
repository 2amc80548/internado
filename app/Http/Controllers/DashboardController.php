<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Mensualidad;
use App\Models\RegistroInternado;
use App\Models\User;
use App\Models\ConfiguracionSistema;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Redirecciona al dashboard correspondiente según el rol del usuario autenticado.
     */
    public function index()
    {
        $rol = auth()->user()->rol->nombre ?? '';

        if ($rol === 'Superadmin') {
            return redirect()->route('superadmin.dashboard');
        } elseif ($rol === 'Encargada') {
            return redirect()->route('encargada.dashboard');
        } elseif ($rol === 'Estudiante') {
            return redirect()->route('estudiante.portal');
        }

        return Inertia::render('Dashboard');
    }

    /**
     * Carga las métricas completas y renderiza el Dashboard para el rol Superadmin.
     */
    public function superadmin()
    {
        // Conteo de estudiantes activos, egresados e inscripciones
        $activeStudents = Estudiante::where('estado_global', 'Activo')->count();
        $bachilleresCount = Estudiante::where('estado_global', 'Bachiller')->count();
        $graduadosBthCount = Estudiante::where('estado_global', 'Graduado BTH')->count();
        $pendingAccounts = User::where('estado_cuenta', 'Pendiente')->count();
        
        // Suma de recaudaciones y verificación de deudas pendientes
        $montoTotalRecaudado = (float)Mensualidad::where('estado', 'Pagado')->sum('monto');
        $pendingPayments = Mensualidad::whereIn('estado', ['Pendiente', 'Pendiente_Verificacion'])->count();
        $totalPayments = Mensualidad::count();
        $paidPayments = Mensualidad::where('estado', 'Pagado')->count();
        
        $porcentajeCobro = $totalPayments > 0 ? round(($paidPayments / $totalPayments) * 100) : 0;
        
        // Últimas inscripciones y verificaciones de pagos por QR
        $recentInscriptions = RegistroInternado::with(['estudiante.persona', 'gestion'])->latest()->take(5)->get();
        $recentPayments = Mensualidad::with(['registroInternado.estudiante.persona'])->where('estado', 'Pendiente_Verificacion')->latest()->take(5)->get();

        // Historial de recaudación mensual para la gestión activa del año actual
        $currentYear = date('Y');
        $currentYearPaid = Mensualidad::where('estado', 'Pagado')
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
    }

    /**
     * Carga las métricas y evalúa permisos para renderizar el Dashboard del rol Encargada.
     */
    public function encargada()
    {
        if (auth()->user()->rol?->nombre === 'Encargada') {
            $config = ConfiguracionSistema::first();
            $permisos = $config ? ($config->permisos_encargada ?? []) : [];
            
            // Si no tiene acceso al Dashboard, redirige a su primer módulo asignado
            if (!in_array('dashboard', $permisos)) {
                if (empty($permisos)) {
                    abort(403, 'No tiene ningún módulo asignado.');
                }
                
                $routeMap = [
                    'estudiantes.index' => 'estudiantes.index',
                    'users.index' => 'users.index',
                    'comunidades.index' => 'comunidades.index',
                    'cursos.index' => 'cursos.index',
                    'cursos-bth.index' => 'cursos-bth.index',
                    'mensualidades.index' => 'mensualidades.index',
                    'incidencias.index' => 'incidencias.index',
                ];
                
                foreach ($permisos as $p) {
                    if (isset($routeMap[$p])) {
                        return redirect()->route($routeMap[$p]);
                    }
                }
                abort(403, 'No tiene permiso para acceder al Dashboard.');
            }
        }

        // Si tiene el permiso del Dashboard, carga exactamente las mismas métricas que el Superadmin
        $activeStudents = Estudiante::where('estado_global', 'Activo')->count();
        $bachilleresCount = Estudiante::where('estado_global', 'Bachiller')->count();
        $graduadosBthCount = Estudiante::where('estado_global', 'Graduado BTH')->count();
        $pendingAccounts = User::where('estado_cuenta', 'Pendiente')->count();
        
        $montoTotalRecaudado = (float)Mensualidad::where('estado', 'Pagado')->sum('monto');
        $pendingPayments = Mensualidad::whereIn('estado', ['Pendiente', 'Pendiente_Verificacion'])->count();
        $totalPayments = Mensualidad::count();
        $paidPayments = Mensualidad::where('estado', 'Pagado')->count();
        
        $porcentajeCobro = $totalPayments > 0 ? round(($paidPayments / $totalPayments) * 100) : 0;
        
        $recentInscriptions = RegistroInternado::with(['estudiante.persona', 'gestion'])->latest()->take(5)->get();
        $recentPayments = Mensualidad::with(['registroInternado.estudiante.persona'])->where('estado', 'Pendiente_Verificacion')->latest()->take(5)->get();

        $currentYear = date('Y');
        $currentYearPaid = Mensualidad::where('estado', 'Pagado')
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
            'role' => 'Encargada',
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
    }

    /**
     * Carga el historial, calificaciones e incidencias y renderiza el Portal del Estudiante.
     */
    public function estudiante()
    {
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

        // Obtener el registro actual y configuración activa del sistema
        $registroActual = $estudiante ? $estudiante->registrosInternado->first() : null;
        $configuracion = ConfiguracionSistema::first();

        return Inertia::render('Student/Portal', [
            'role' => 'Estudiante',
            'estudiante' => $estudiante,
            'registroActual' => $registroActual,
            'configuracion' => $configuracion,
        ]);
    }
}
