<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface MetricData {
    activeStudents: number;
    bachilleresCount: number;
    graduadosBthCount: number;
    pendingAccounts: number;
    montoTotalRecaudado: number;
    pendingPayments: number;
    totalPayments: number;
    paidPayments: number;
    porcentajeCobro: number;
    recaudacionMensual: { mes: string; total: number }[];
}

interface Persona {
    ci: string;
    nombre: string;
    ap_paterno: string;
    ap_materno: string | null;
}

interface Estudiante {
    id: number;
    persona_ci: string;
    estado_global: string;
    persona: Persona;
}

interface Mensualidad {
    id: number;
    mes: string;
    monto: number;
    estado: string;
    ruta_comprobante_qr: string | null;
    registro_internado: {
        estudiante: Estudiante;
    };
}

interface Inscription {
    id: number;
    estado_anual: string;
    created_at: string;
    estudiante: Estudiante;
}

const props = defineProps<{
    role: string;
    metrics: MetricData;
    recentInscriptions: Inscription[];
    recentPayments: Mensualidad[];
}>();

// Estado del Modal de Validación
const validatingPayment = ref<Mensualidad | null>(null);

const form = useForm({
    estado: '',
    ruta_comprobante_qr: null as string | null,
});

const openValidationModal = (payment: Mensualidad) => {
    validatingPayment.value = payment;
};

const closeValidationModal = () => {
    validatingPayment.value = null;
    form.reset();
};

const approvePayment = () => {
    if (!validatingPayment.value) return;
    form.estado = 'Pagado';
    form.put(route('mensualidades.update', validatingPayment.value.id), {
        onSuccess: () => closeValidationModal(),
    });
};

const rejectPayment = () => {
    if (!validatingPayment.value) return;
    form.estado = 'Pendiente';
    form.ruta_comprobante_qr = null; // Borrar comprobante
    form.put(route('mensualidades.update', validatingPayment.value.id), {
        onSuccess: () => closeValidationModal(),
    });
};

const maxTotalRecaudado = computed(() => {
    if (!props.metrics?.recaudacionMensual) return 1;
    const totals = props.metrics.recaudacionMensual.map(m => m.total);
    const max = Math.max(...totals);
    return max > 0 ? max : 1;
});
</script>

<template>
    <Head title="Panel Administrativo" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                Panel Administrativo
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Welcome Section with Premium Gradient -->
                <div class="p-6 sm:p-8 bg-gradient-to-r from-teal-600 via-indigo-600 to-purple-600 rounded-3xl shadow-xl text-white relative overflow-hidden group">
                    <div class="absolute -right-10 -bottom-10 opacity-10 group-hover:scale-110 transition-transform duration-500">
                        <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v-2H7v-2h7V9H7V7h7c1.1 0 2 .9 2 2v6c0 1.1-.9 2-2 2z"></path></svg>
                    </div>
                    <div class="relative z-10 space-y-2">
                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase bg-white/20 text-white backdrop-blur-sm border border-white/10 tracking-widest">Panel de Control</span>
                        <h3 class="text-3xl sm:text-4xl font-extrabold tracking-tight">¡Hola, {{ role }}!</h3>
                        <p class="text-teal-100 max-w-2xl text-sm sm:text-base font-semibold leading-relaxed">
                            Aquí tienes el control central del internado. Gestiona cobros, valida cuentas y realiza el seguimiento de la trayectoria académica de forma unificada.
                        </p>
                    </div>
                </div>

                <!-- 4 Metrics Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Estudiantes Activos -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center space-x-4 transform transition hover:-translate-y-1 hover:shadow-md duration-200">
                        <div class="p-4 bg-emerald-50 text-emerald-600 rounded-2xl shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Estudiantes Activos</p>
                            <h4 class="text-3xl font-black text-slate-800 mt-1">{{ metrics.activeStudents }}</h4>
                            <p class="text-[10px] text-emerald-600 font-bold mt-1">Cursando la gestión activa</p>
                        </div>
                    </div>

                    <!-- Estudiantes Bachilleres -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center space-x-4 transform transition hover:-translate-y-1 hover:shadow-md duration-200">
                        <div class="p-4 bg-indigo-50 text-indigo-600 rounded-2xl shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Graduados Bachiller</p>
                            <h4 class="text-3xl font-black text-slate-800 mt-1">{{ metrics.bachilleresCount }}</h4>
                            <p class="text-[10px] text-indigo-600 font-bold mt-1">Egresados históricos</p>
                        </div>
                    </div>

                    <!-- Pendientes de Habilitación -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center space-x-4 transform transition hover:-translate-y-1 hover:shadow-md duration-200">
                        <div class="p-4 bg-amber-50 text-amber-600 rounded-2xl shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Por Habilitar</p>
                            <h4 class="text-3xl font-black text-slate-800 mt-1">{{ metrics.pendingAccounts }}</h4>
                            <p class="text-[10px] text-amber-600 font-bold mt-1">Cuentas pendientes de aprobación</p>
                        </div>
                    </div>

                    <!-- Especialidades BTH -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center space-x-4 transform transition hover:-translate-y-1 hover:shadow-md duration-200">
                        <div class="p-4 bg-purple-50 text-purple-600 rounded-2xl shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Técnicos BTH</p>
                            <h4 class="text-3xl font-black text-slate-800 mt-1">{{ metrics.graduadosBthCount }}</h4>
                            <p class="text-[10px] text-purple-600 font-bold mt-1">Estudiantes BTH graduados</p>
                        </div>
                    </div>
                </div>

                <!-- Desempeño Financiero y Gráficas de Recaudación -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Avance de Caja y Porcentajes -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm space-y-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between pb-4 border-b border-slate-50">
                                <div>
                                    <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider">Caja y Recaudación</h4>
                                    <p class="text-[11px] text-slate-400 font-semibold">Estado de mensualidades escolares</p>
                                </div>
                                <span class="p-2 bg-teal-50 text-teal-600 rounded-xl">Bs.</span>
                            </div>
                            
                            <div class="mt-6 text-center space-y-2">
                                <span class="text-xs text-slate-400 font-bold uppercase tracking-widest block">Total Recaudado</span>
                                <h2 class="text-4xl font-black text-teal-600">Bs. {{ metrics.montoTotalRecaudado.toLocaleString() }}</h2>
                            </div>
                            
                            <div class="mt-6 space-y-4">
                                <div>
                                    <div class="flex justify-between items-center text-xs text-slate-500 font-bold mb-1">
                                        <span>Avance de Cobro</span>
                                        <span class="text-teal-600 font-black">{{ metrics.porcentajeCobro }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                        <div class="bg-gradient-to-r from-teal-500 to-teal-600 h-2.5 rounded-full transition-all duration-500" :style="{ width: metrics.porcentajeCobro + '%' }"></div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4 text-xs font-semibold pt-4">
                                    <div class="p-3 bg-teal-50/50 rounded-xl border border-teal-50">
                                        <span class="text-slate-400 block text-[10px] uppercase">Pagos Registrados</span>
                                        <span class="text-slate-800 font-black text-base">{{ metrics.paidPayments }}</span>
                                    </div>
                                    <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-50">
                                        <span class="text-slate-400 block text-[10px] uppercase">Pagos Pendientes</span>
                                        <span class="text-slate-800 font-black text-base">{{ metrics.pendingPayments }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-400 italic text-center font-medium">Los importes consideran deudas cobradas de la gestión activa.</p>
                    </div>

                    <!-- Gráfica de Recaudación Mensual (Pure CSS Premium Bar Chart) -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex flex-col justify-between lg:col-span-2">
                        <div>
                            <div class="flex items-center justify-between pb-4 border-b border-slate-50">
                                <div>
                                    <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider">Historial de Cobros por Mes</h4>
                                    <p class="text-[11px] text-slate-400 font-semibold">Caja mensual acumulada en la gestión activa {{ new Date().getFullYear() }}</p>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-50 text-indigo-600">Mensual</span>
                            </div>
                            
                            <!-- Pure CSS / SVG Proportional Bar Chart -->
                            <div class="h-56 flex items-end justify-between gap-2.5 pt-8 px-2">
                                <div v-for="m in metrics.recaudacionMensual" :key="m.mes" class="flex-grow flex flex-col items-center group relative h-full justify-end">
                                    <!-- Bar Container -->
                                    <div class="w-full bg-slate-50/75 rounded-t-lg relative flex items-end h-40">
                                        <!-- Scaled Height Bar with Hover Transitions -->
                                        <div class="w-full bg-gradient-to-t from-teal-500 to-indigo-600 rounded-t-lg group-hover:from-indigo-600 group-hover:to-purple-600 transition-all duration-300 shadow-sm" 
                                             :style="{ height: (m.total > 0 ? Math.min(100, Math.max(10, (m.total / maxTotalRecaudado) * 100)) : 0) + '%' }">
                                            
                                            <!-- Hover tooltip showing cash details -->
                                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 bg-slate-900 text-white text-[10px] font-black py-1.5 px-2.5 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap shadow-xl z-20">
                                                Bs. {{ m.total.toLocaleString() }}
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-wide">{{ m.mes }}</span>
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-400 italic text-center font-medium mt-2">Pasa el cursor sobre las barras para ver los montos exactos por mes.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Inscripciones Recientes -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Inscripciones Recientes</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-600">Nuevos Estudiantes</span>
                            </div>
                            <div class="p-6">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-left text-sm whitespace-nowrap">
                                        <thead class="uppercase tracking-wider text-slate-400 font-bold text-xs">
                                            <tr>
                                                <th scope="col" class="pb-4">Estudiante</th>
                                                <th scope="col" class="pb-4">CI</th>
                                                <th scope="col" class="pb-4">Estado Ciclo</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-slate-600">
                                            <tr v-for="insc in recentInscriptions" :key="insc.id" class="border-t border-slate-50 hover:bg-slate-50/50 transition">
                                                <td class="py-4 font-bold text-slate-800">
                                                    {{ insc.estudiante.persona.nombre }} {{ insc.estudiante.persona.ap_paterno }}
                                                </td>
                                                <td class="py-4 text-xs font-semibold text-slate-500">{{ insc.estudiante.persona.ci }}</td>
                                                <td class="py-4">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        {{ insc.estado_anual }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr v-if="recentInscriptions.length === 0">
                                                <td colspan="3" class="py-8 text-center text-slate-400 font-bold text-xs">No hay inscripciones recientes en la gestión.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mensualidades por Verificar -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Validación de Pagos (QR)</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-50 text-indigo-600">Verificaciones Pendientes</span>
                            </div>
                            <div class="p-6">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-left text-sm whitespace-nowrap">
                                        <thead class="uppercase tracking-wider text-slate-400 font-bold text-xs">
                                            <tr>
                                                <th scope="col" class="pb-4">Estudiante</th>
                                                <th scope="col" class="pb-4">Mes</th>
                                                <th scope="col" class="pb-4">Monto</th>
                                                <th scope="col" class="pb-4 text-right">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-slate-600">
                                            <tr v-for="pago in recentPayments" :key="pago.id" class="border-t border-slate-50 hover:bg-slate-50/50 transition">
                                                <td class="py-4 font-bold text-slate-800">
                                                    {{ pago.registro_internado.estudiante.persona.nombre }} 
                                                    {{ pago.registro_internado.estudiante.persona.ap_paterno }}
                                                </td>
                                                <td class="py-4 text-xs font-semibold text-slate-500">{{ pago.mes }}</td>
                                                <td class="py-4 font-black text-slate-800">Bs. {{ pago.monto }}</td>
                                                <td class="py-4 text-right">
                                                    <button @click="openValidationModal(pago)" class="inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-bold rounded-xl text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none transition-colors">
                                                        Verificar QR
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="recentPayments.length === 0">
                                                <td colspan="4" class="py-8 text-center text-slate-400 font-bold text-xs">No hay comprobantes QR pendientes de verificación.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal de Verificación QR -->
                <div v-if="validatingPayment" class="fixed inset-0 z-50 overflow-y-auto animate-fade-in" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        
                        <!-- Overlay -->
                        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="closeValidationModal" aria-hidden="true"></div>

                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                        <!-- Modal Content -->
                        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">
                            <div class="bg-white px-6 pt-6 pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="text-center sm:text-left w-full space-y-4">
                                        <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                                            <div class="p-2 bg-teal-50 text-teal-600 rounded-xl">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </div>
                                            <h3 class="text-lg font-black text-slate-800 leading-tight" id="modal-title">
                                                Verificar Comprobante
                                            </h3>
                                        </div>
                                        
                                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 space-y-2">
                                            <p class="text-sm text-slate-600 font-semibold">
                                                <span class="text-slate-400 uppercase text-[10px] tracking-widest block mb-0.5">Estudiante</span>
                                                {{ validatingPayment.registro_internado.estudiante.persona.nombre }} {{ validatingPayment.registro_internado.estudiante.persona.ap_paterno }}
                                            </p>
                                            <p class="text-sm text-slate-600 font-semibold">
                                                <span class="text-slate-400 uppercase text-[10px] tracking-widest block mb-0.5">Mes & Monto</span>
                                                Mes: <span class="text-slate-800 font-black">{{ validatingPayment.mes }}</span> &mdash; 
                                                Monto: <span class="text-slate-800 font-black">Bs. {{ validatingPayment.monto }}</span>
                                            </p>
                                            
                                            <!-- Visor de Imagen QR -->
                                            <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-100 flex items-center justify-center min-h-[250px] shadow-inner mt-4">
                                                <img 
                                                    v-if="validatingPayment.ruta_comprobante_qr"
                                                    :src="`/storage/${validatingPayment.ruta_comprobante_qr}`" 
                                                    alt="Comprobante QR" 
                                                    class="max-w-full h-auto object-contain"
                                                />
                                                <span v-else class="text-slate-400 text-sm font-bold">No hay comprobante QR cargado</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse rounded-b-3xl border-t border-slate-100">
                                <button @click="approvePayment" :disabled="form.processing" type="button" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-green-600 text-sm font-bold text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto transition">
                                    {{ form.processing ? 'Aprobando...' : 'Confirmar Pago' }}
                                </button>
                                <button @click="rejectPayment" :disabled="form.processing" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-red-50 text-sm font-bold text-red-600 hover:bg-red-100 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto transition">
                                    Rechazar (Eliminar QR)
                                </button>
                                <button @click="closeValidationModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-200 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 sm:mt-0 sm:ml-3 sm:w-auto transition">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
