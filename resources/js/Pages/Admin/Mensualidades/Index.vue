<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';

// Importación de los componentes modulares hijos
import MensualidadesTable from './Partials/MensualidadesTable.vue';
import GenerarMasivoModal from './Partials/GenerarMasivoModal.vue';
import CobrarModal from './Partials/CobrarModal.vue';
import AnularModal from './Partials/AnularModal.vue';
import VerAnuladoModal from './Partials/VerAnuladoModal.vue';

// Propiedades recibidas desde el servidor a través de Inertia
const props = defineProps<{
    mensualidades: any[];
    estudiantesActivos?: any[];
    gestiones?: any[];
}>();

// Lista estática de meses escolares para cobro regular
const mesesDisponibles = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

// Formulario de Inertia para la generación masiva de deudas/cobros
const formGenerar = useForm({
    concepto_tipo: 'mes', // 'mes' o 'personalizado'
    mes: 'Febrero',
    concepto_personalizado: '',
    monto: 150,
    registro_internado_ids: [] as number[]
});

// Formulario de Inertia para procesar y consolidar pagos individuales
const formPagar = useForm({
    estado: 'Pagado',
    metodo_pago: 'Efectivo',
    fecha_pago: new Date().toISOString().substring(0, 10)
});

// Filtros reactivos de búsqueda en la sección de caja
const searchQuery = ref('');
const cargarTodosSwitch = ref(false);

// Función que realiza la petición HTTP con los filtros de caja activos al servidor
const performServerSearch = () => {
    router.get(route('mensualidades.index'), {
        search: searchQuery.value,
        estado: estadoFilter.value,
        gestion_id: gestionIdFilter.value,
        cargar_todos: cargarTodosSwitch.value ? '1' : '0'
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['mensualidades']
    });
};

// Observadores para reaccionar al cambio de filtros y recargar los datos
watch(cargarTodosSwitch, () => {
    performServerSearch();
});

watch(() => [
    estadoFilter.value,
    gestionIdFilter.value
], () => {
    performServerSearch();
});

// Hook inicial para parsear parámetros de la URL al cargar la página
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('cargar_todos') === '1') {
        cargarTodosSwitch.value = true;
    }
    if (urlParams.get('search')) {
        searchQuery.value = urlParams.get('search') || '';
    }
});

// Filtros avanzados del panel de control de mensualidades
const estadoFilter = ref('Pendiente'); // Muestra 'Pendientes' por defecto
const fechaInicioFilter = ref('');
const fechaFinFilter = ref('');
const gestionIdFilter = ref('');

// Estados de control para la visibilidad de los modales hijos
const showGenerarModal = ref(false);
const showPagarModal = ref(false);
const showAnularModal = ref(false);
const showViewAnuladoModal = ref(false);

// Registro seleccionado para operaciones específicas (Cobros, Anulaciones, Auditorías)
const mensualidadSeleccionada = ref<any>(null);
const mensualidadAAnular = ref<any>(null);
const mensualidadVerAnulada = ref<any>(null);

// Formulario de Inertia para registrar motivos de anulación y confirmar credenciales
const formAnular = useForm({
    estado: 'Anulado',
    motivo_anulacion: '',
    password: ''
});

// Procesa el filtrado en el cliente para el buscador y las fechas adicionales
const filteredMensualidades = computed(() => {
    let results = props.mensualidades;
    
    if (estadoFilter.value) {
        results = results.filter(m => {
            if (estadoFilter.value === 'Pendiente') {
                return m.estado === 'Pendiente' || m.estado === 'Pendiente_Verificacion';
            }
            return m.estado === estadoFilter.value;
        });
    }

    if (gestionIdFilter.value) {
        results = results.filter(m => m.registro_internado && String(m.registro_internado.gestion_id) === String(gestionIdFilter.value));
    }

    if (fechaInicioFilter.value) {
        results = results.filter(m => {
            if (!m.fecha_pago) return false;
            return m.fecha_pago >= fechaInicioFilter.value;
        });
    }

    if (fechaFinFilter.value) {
        results = results.filter(m => {
            if (!m.fecha_pago) return false;
            return m.fecha_pago <= fechaFinFilter.value;
        });
    }

    if (!searchQuery.value) return results;
    const q = searchQuery.value.toLowerCase();
    return results.filter(m => 
        m.registro_internado?.estudiante?.persona?.nombre.toLowerCase().includes(q) ||
        m.registro_internado?.estudiante?.persona?.ap_paterno.toLowerCase().includes(q) ||
        m.registro_internado?.estudiante?.persona?.ci.includes(q) ||
        m.mes.toLowerCase().includes(q)
    );
});

// Exporta toda la planilla filtrada actual a CSV con formato compatible Excel y soporte UTF-8
const exportToExcel = () => {
    if (filteredMensualidades.value.length === 0) {
        alert("No hay registros filtrados para exportar.");
        return;
    }
    
    let csvContent = "\uFEFF";
    
    const headers = [
        "Estudiante",
        "Documento CI",
        "Curso",
        "Gestión Académica",
        "Pabellón/Cama",
        "Mes",
        "Monto Original",
        "Monto Total",
        "Estado",
        "Método de Pago",
        "Fecha de Pago",
        "Motivo de Anulación"
    ];
    csvContent += headers.join(";") + "\n";
    
    filteredMensualidades.value.forEach(m => {
        const estudiante = `${m.registro_internado?.estudiante?.persona?.nombre || ''} ${m.registro_internado?.estudiante?.persona?.ap_paterno || ''} ${m.registro_internado?.estudiante?.persona?.ap_materno || ''}`.trim();
        const ci = m.registro_internado?.estudiante?.persona?.ci || '';
        const curso = m.registro_internado?.curso?.nombre || 'Ninguno';
        const gestion = m.registro_internado?.gestion?.nombre_gestion || '';
        const habitacion = `${m.registro_internado?.pabellon || 'S/N'} - Cama ${m.registro_internado?.cama || 'S/N'}`;
        const mes = m.mes || '';
        const montoOrig = `Bs. ${m.monto}`;
        const montoTot = `Bs. ${m.total}`;
        const estado = m.estado || '';
        const metodo = m.metodo_pago || 'Ninguno';
        const fecha = m.fecha_pago || 'Ninguna';
        const motivo = (m.motivo_anulacion || '').replace(/[\n\r;]/g, " ");
        
        const row = [
            estudiante,
            ci,
            curso,
            gestion,
            habitacion,
            mes,
            montoOrig,
            montoTot,
            estado,
            metodo,
            fecha,
            motivo
        ];
        csvContent += row.map(cell => `"${cell}"`).join(";") + "\n";
    });
    
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `Reporte_Mensualidades_${new Date().toISOString().substring(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Cómputo global de ingresos recaudados en caja
const totalRecaudado = computed(() => {
    return props.mensualidades
         .filter(m => m.estado === 'Pagado')
         .reduce((sum, m) => sum + Number(m.total), 0);
});

// Cómputo global de saldos por cobrar
const totalPendiente = computed(() => {
    return props.mensualidades
         .filter(m => m.estado === 'Pendiente' || m.estado === 'Pendiente_Verificacion')
         .reduce((sum, m) => sum + Number(m.total), 0);
});

// Cómputo global de pagos capturados en efectivo físico
const totalEfectivo = computed(() => {
    return props.mensualidades
         .filter(m => m.estado === 'Pagado' && m.metodo_pago === 'Efectivo')
         .reduce((sum, m) => sum + Number(m.total), 0);
});

// Cómputo global de pagos capturados vía canales QR/Digitales
const totalQR = computed(() => {
    return props.mensualidades
         .filter(m => m.estado === 'Pagado' && m.metodo_pago === 'QR')
         .reduce((sum, m) => sum + Number(m.total), 0);
});

// Porcentaje de cobranza consolidada
const porcentajeCobro = computed(() => {
    const total = totalRecaudado.value + totalPendiente.value;
    if (total === 0) return 0;
    return Math.round((totalRecaudado.value / total) * 100);
});

// Envía el formulario para crear las deudas mensuales a los estudiantes activos
const submitGenerar = () => {
    formGenerar.post(route('mensualidades.generar_masivo'), {
        preserveScroll: true,
        onSuccess: () => {
            showGenerarModal.value = false;
            formGenerar.registro_internado_ids = [];
        }
    });
};

// Abre el modal de cobranza individual asignando valores predeterminados
const openPagarModal = (m: any) => {
    mensualidadSeleccionada.value = m;
    formPagar.estado = 'Pagado';
    formPagar.metodo_pago = 'Efectivo';
    formPagar.fecha_pago = new Date().toISOString().substring(0, 10);
    showPagarModal.value = true;
};

// Procesa el registro del cobro del estudiante
const submitPagar = () => {
    formPagar.put(route('mensualidades.update', mensualidadSeleccionada.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPagarModal.value = false;
            alert('Pago registrado correctamente.');
        }
    });
};

// Abre el modal para capturar la justificación de anulación
const openAnularModal = (m: any) => {
    mensualidadAAnular.value = m;
    formAnular.motivo_anulacion = '';
    formAnular.password = '';
    formAnular.clearErrors();
    showAnularModal.value = true;
};

// Procesa la anulación del cobro registrado mediante credenciales
const submitAnular = () => {
    formAnular.put(route('mensualidades.update', mensualidadAAnular.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAnularModal.value = false;
            alert('Pago anulado exitosamente.');
        }
    });
};

// Abre el modal visor para auditar el motivo de anulación
const openVerAnuladoModal = (m: any) => {
    mensualidadVerAnulada.value = m;
    showViewAnuladoModal.value = true;
};

// Elimina permanentemente el registro de deuda de un estudiante (siempre que esté pendiente)
const deleteMensualidad = (id: number) => {
    if (confirm('¿Estás seguro de eliminar este registro de deuda?')) {
        router.delete(route('mensualidades.destroy', id), {
            preserveScroll: true,
            onError: (err) => {
                if (err.delete) {
                    alert(err.delete);
                }
            }
        });
    }
};
</script>

<template>
    <Head title="Gestión de Mensualidades" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                Control de Mensualidades
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- KPI Dashboard Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Recaudado -->
                    <div class="bg-gradient-to-br from-teal-500 to-teal-700 text-white rounded-2xl p-6 shadow-lg shadow-teal-100 flex flex-col justify-between relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
                        <div class="absolute -right-4 -bottom-4 text-teal-400/20 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.554-.589 1.448-.589 2.002 0l.207.22 1.632-1.733a3.5 3.5 0 00-5.267 0l1.426 1.513zm4.773 1.258A3.5 3.5 0 0013.25 4.5h-6.5a3.5 3.5 0 00-.77 6.918l1.427 1.513c.554-.588 1.448-.588 2.002 0l.207.22 1.632-1.733z"></path></svg>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-teal-100 uppercase tracking-widest">Total Recaudado</span>
                            <h3 class="text-3xl font-extrabold mt-2">Bs. {{ totalRecaudado.toLocaleString() }}</h3>
                        </div>
                        <div class="mt-4">
                            <div class="flex justify-between items-center text-xs text-teal-100 mb-1">
                                <span>Avance de Cobro</span>
                                <span class="font-bold">{{ porcentajeCobro }}%</span>
                            </div>
                            <div class="w-full bg-teal-800/40 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-white h-1.5 rounded-full transition-all duration-500" :style="{ width: porcentajeCobro + '%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Pendiente -->
                    <div class="bg-gradient-to-br from-amber-500 to-orange-600 text-white rounded-2xl p-6 shadow-lg shadow-amber-100 flex flex-col justify-between relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
                        <div class="absolute -right-4 -bottom-4 text-amber-300/20 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-amber-100 uppercase tracking-widest">Total Pendiente</span>
                            <h3 class="text-3xl font-extrabold mt-2">Bs. {{ totalPendiente.toLocaleString() }}</h3>
                        </div>
                        <div class="mt-4">
                            <span class="text-xs text-amber-100">Cuentas por cobrar activas</span>
                        </div>
                    </div>

                    <!-- Efectivo Físico -->
                    <div class="bg-gradient-to-br from-sky-500 to-indigo-600 text-white rounded-2xl p-6 shadow-lg shadow-sky-100 flex flex-col justify-between relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
                        <div class="absolute -right-4 -bottom-4 text-sky-300/20 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-sky-100 uppercase tracking-widest">Efectivo Físico</span>
                            <h3 class="text-3xl font-extrabold mt-2">Bs. {{ totalEfectivo.toLocaleString() }}</h3>
                        </div>
                        <div class="mt-4">
                            <span class="text-xs text-sky-100">Recaudado presencialmente</span>
                        </div>
                    </div>

                    <!-- Transferencias QR -->
                    <div class="bg-gradient-to-br from-purple-500 to-pink-600 text-white rounded-2xl p-6 shadow-lg shadow-purple-100 flex flex-col justify-between relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
                        <div class="absolute -right-4 -bottom-4 text-purple-300/20 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zm3 3a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H9a1 1 0 01-1-1V9zm2 2v-1H9v1h1zm3-7a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1V4zm2 2V5h1v1h-1zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-purple-100 uppercase tracking-widest">Transferencia / QR</span>
                            <h3 class="text-3xl font-extrabold mt-2">Bs. {{ totalQR.toLocaleString() }}</h3>
                        </div>
                        <div class="mt-4">
                            <span class="text-xs text-purple-100">Canales digitales</span>
                        </div>
                    </div>
                </div>

                <!-- Super Filtros y Panel de Control -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Filtros de Pagos y Caja</h3>
                            <p class="text-xs text-gray-500">Administra, filtra y anula cobros escolares de la gestión activa</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button @click="exportToExcel" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-5 rounded-xl shadow-md transition text-sm flex items-center gap-2 transform hover:scale-[1.02]">
                                <svg class="w-4 h-4 text-emerald-100" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-4H8V8h5v2z"/>
                                </svg>
                                📊 Descargar Excel
                            </button>
                            <button @click="showGenerarModal = true" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded-xl shadow-sm transition text-sm">
                                + Generar Deudas del Mes
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Búsqueda -->
                        <div class="relative">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Búsqueda General</label>
                            <input v-model="searchQuery" @keydown.enter="performServerSearch" type="text" placeholder="Nombre, CI o Mes... (Enter)" class="w-full pl-10 pr-4 py-2 border-gray-200 rounded-xl shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm font-semibold text-gray-700 placeholder-gray-400">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 bottom-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>

                        <!-- Filtro Estado -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Estado</label>
                            <select v-model="estadoFilter" class="w-full rounded-xl border-gray-200 shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm font-bold text-gray-700 bg-white">
                                <option value="">Todos los Estados</option>
                                <option value="Pendiente">Pendientes de Pago</option>
                                <option value="Pagado">Pagados</option>
                                <option value="Anulado">Anulados</option>
                            </select>
                        </div>

                        <!-- Filtro Gestión -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Gestión Académica</label>
                            <select v-model="gestionIdFilter" class="w-full rounded-xl border-gray-200 shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm font-bold text-gray-700 bg-white">
                                <option value="">Todas las Gestiones</option>
                                <option v-for="g in gestiones" :key="g.id" :value="g.id">{{ g.nombre_gestion }}</option>
                            </select>
                        </div>

                        <!-- Fecha Inicio -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Fecha de Pago Desde</label>
                            <input v-model="fechaInicioFilter" type="date" class="w-full rounded-xl border-gray-200 shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm font-semibold text-gray-700">
                        </div>

                        <!-- Fecha Fin -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Fecha de Pago Hasta</label>
                            <input v-model="fechaFinFilter" type="date" class="w-full rounded-xl border-gray-200 shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm font-semibold text-gray-700">
                        </div>
                    </div>

                    <!-- Activador Carga Diferida (Rendimiento) -->
                    <div class="mt-5 pt-4 border-t border-gray-150 flex items-center justify-between gap-4 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-500 animate-pulse" v-if="props.mensualidades.length === 0"></span>
                            <span class="text-xs text-gray-500 font-bold">
                                {{ props.mensualidades.length === 0 ? 'Carga optimizada: listado oculto inicialmente para acelerar el sistema.' : 'Listado dinámico cargado exitosamente.' }}
                            </span>
                        </div>
                        <label class="inline-flex items-center gap-2.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 px-4 py-2 rounded-xl cursor-pointer transition select-none">
                            <input type="checkbox" v-model="cargarTodosSwitch" class="h-4.5 w-4.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 cursor-pointer">
                            <span class="text-xs font-black text-gray-700">Cargar listado completo de mensualidades</span>
                        </label>
                    </div>

                </div>

                <!-- Tabla Principal de Mensualidades (Modularizada) -->
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                    <div v-if="props.mensualidades.length === 0" class="py-20 px-8 text-center max-w-lg mx-auto">
                        <div class="h-16 w-16 rounded-full bg-teal-50 text-teal-600 flex items-center justify-center mx-auto mb-5 text-3xl shadow-sm">
                            💵
                        </div>
                        <h3 class="text-lg font-black text-gray-800 mb-1.5">Listado de Mensualidades (Pagos)</h3>
                        <p class="text-sm text-gray-500 leading-relaxed mb-6">Para garantizar la máxima velocidad de carga, la lista no se carga por defecto. Active el interruptor de carga completa, realice una búsqueda y presione Enter o aplique un filtro para visualizar los registros.</p>
                        <button @click="cargarTodosSwitch = true" class="inline-flex items-center justify-center bg-teal-600 hover:bg-teal-700 text-white font-black py-2.5 px-6 rounded-xl shadow-md transition text-xs">
                            Cargar Todo el Listado
                        </button>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <MensualidadesTable 
                            :filtered-mensualidades="filteredMensualidades"
                            @pay="openPagarModal"
                            @delete="deleteMensualidad"
                            @anular="openAnularModal"
                            @viewAnulado="openVerAnuladoModal"
                        />
                    </div>
                </div>

                <!-- Modal Generar Deudas Masivas (Modularizado) -->
                <GenerarMasivoModal 
                    :show-generar-modal="showGenerarModal"
                    :meses-disponibles="mesesDisponibles"
                    :form-generar="formGenerar"
                    :estudiantes-activos="estudiantesActivos || []"
                    @close="showGenerarModal = false"
                    @submit="submitGenerar"
                />

                <!-- Modal Registrar Pago Individual (Modularizado) -->
                <CobrarModal 
                    :show-pagar-modal="showPagarModal"
                    :mensualidad-seleccionada="mensualidadSeleccionada"
                    :form-pagar="formPagar"
                    @close="showPagarModal = false"
                    @submit="submitPagar"
                />

                <!-- Modal Anular Pago Con Contraseña (Modularizado) -->
                <AnularModal 
                    :show-anular-modal="showAnularModal"
                    :mensualidad-a-anular="mensualidadAAnular"
                    :form-anular="formAnular"
                    @close="showAnularModal = false"
                    @submit="submitAnular"
                />

                <!-- Modal Auditar Pago Anulado (Modularizado) -->
                <VerAnuladoModal 
                    :show-view-anulado-modal="showViewAnuladoModal"
                    :mensualidad-ver-anulada="mensualidadVerAnulada"
                    @close="showViewAnuladoModal = false"
                />

            </div>
        </div>
    </AuthenticatedLayout>
</template>
