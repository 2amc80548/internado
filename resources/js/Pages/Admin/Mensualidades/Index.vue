<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps<{
    mensualidades: any[];
    estudiantesActivos?: any[];
    gestiones?: any[];
}>();

const mesesDisponibles = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

const formGenerar = useForm({
    concepto_tipo: 'mes', // 'mes' o 'personalizado'
    mes: 'Febrero',
    concepto_personalizado: '',
    monto: 150,
    registro_internado_ids: [] as number[]
});

const searchStudentInModal = ref('');

const filteredEstudiantesActivos = computed(() => {
    const list = props.estudiantesActivos || [];
    if (!searchStudentInModal.value) return list;
    const q = searchStudentInModal.value.toLowerCase();
    return list.filter(est => 
        est.estudiante?.persona?.nombre.toLowerCase().includes(q) ||
        est.estudiante?.persona?.ap_paterno.toLowerCase().includes(q) ||
        (est.estudiante?.persona?.ap_materno && est.estudiante.persona.ap_materno.toLowerCase().includes(q)) ||
        est.estudiante?.persona?.ci.includes(q)
    );
});

const seleccionarTodos = computed({
    get() {
        const list = filteredEstudiantesActivos.value;
        return list.length > 0 && list.every(e => formGenerar.registro_internado_ids.includes(e.id));
    },
    set(val) {
        const listIds = filteredEstudiantesActivos.value.map(e => e.id);
        if (val) {
            listIds.forEach(id => {
                if (!formGenerar.registro_internado_ids.includes(id)) {
                    formGenerar.registro_internado_ids.push(id);
                }
            });
        } else {
            formGenerar.registro_internado_ids = formGenerar.registro_internado_ids.filter(id => !listIds.includes(id));
        }
    }
});

const formPagar = useForm({
    estado: 'Pagado',
    metodo_pago: 'Efectivo',
    fecha_pago: new Date().toISOString().substring(0, 10)
});

const searchQuery = ref('');
const cargarTodosSwitch = ref(false);

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

watch(cargarTodosSwitch, () => {
    performServerSearch();
});

watch(() => [
    estadoFilter.value,
    gestionIdFilter.value
], () => {
    performServerSearch();
});

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('cargar_todos') === '1') {
        cargarTodosSwitch.value = true;
    }
    if (urlParams.get('search')) {
        searchQuery.value = urlParams.get('search') || '';
    }
});

const estadoFilter = ref('Pendiente'); // Muestra 'Pendientes' por defecto
const fechaInicioFilter = ref('');
const fechaFinFilter = ref('');
const gestionIdFilter = ref('');

const showGenerarModal = ref(false);
const showPagarModal = ref(false);
const showAnularModal = ref(false);
const showViewAnuladoModal = ref(false);

const mensualidadSeleccionada = ref<any>(null);
const mensualidadAAnular = ref<any>(null);
const mensualidadVerAnulada = ref<any>(null);

const formAnular = useForm({
    estado: 'Anulado',
    motivo_anulacion: '',
    password: ''
});

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
            // Solo compara la fecha
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

const exportToExcel = () => {
    if (filteredMensualidades.value.length === 0) {
        alert("No hay registros filtrados para exportar.");
        return;
    }
    
    // Codificación UTF-8 con BOM para soportar tildes, acentos y la letra Ñ en Excel
    let csvContent = "\uFEFF";
    
    // Cabeceras
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
    
    // Filas
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
        const motivo = (m.motivo_anulacion || '').replace(/[\n\r;]/g, " "); // Reemplaza saltos y punto y coma
        
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

const totalRecaudado = computed(() => {
    return props.mensualidades
         .filter(m => m.estado === 'Pagado')
         .reduce((sum, m) => sum + Number(m.total), 0);
});

const totalPendiente = computed(() => {
    return props.mensualidades
         .filter(m => m.estado === 'Pendiente' || m.estado === 'Pendiente_Verificacion')
         .reduce((sum, m) => sum + Number(m.total), 0);
});

const totalEfectivo = computed(() => {
    return props.mensualidades
         .filter(m => m.estado === 'Pagado' && m.metodo_pago === 'Efectivo')
         .reduce((sum, m) => sum + Number(m.total), 0);
});

const totalQR = computed(() => {
    return props.mensualidades
         .filter(m => m.estado === 'Pagado' && m.metodo_pago === 'QR')
         .reduce((sum, m) => sum + Number(m.total), 0);
});

const porcentajeCobro = computed(() => {
    const total = totalRecaudado.value + totalPendiente.value;
    if (total === 0) return 0;
    return Math.round((totalRecaudado.value / total) * 100);
});

const submitGenerar = () => {
    formGenerar.post(route('mensualidades.generar_masivo'), {
        preserveScroll: true,
        onSuccess: () => {
            showGenerarModal.value = false;
            formGenerar.registro_internado_ids = [];
            searchStudentInModal.value = '';
        }
    });
};

const openPagarModal = (m: any) => {
    mensualidadSeleccionada.value = m;
    formPagar.estado = 'Pagado';
    formPagar.metodo_pago = 'Efectivo';
    formPagar.fecha_pago = new Date().toISOString().substring(0, 10);
    showPagarModal.value = true;
};

const submitPagar = () => {
    formPagar.put(route('mensualidades.update', mensualidadSeleccionada.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPagarModal.value = false;
            alert('Pago registrado correctamente.');
        }
    });
};

const openAnularModal = (m: any) => {
    mensualidadAAnular.value = m;
    formAnular.motivo_anulacion = '';
    formAnular.password = '';
    formAnular.clearErrors();
    showAnularModal.value = true;
};

const submitAnular = () => {
    formAnular.put(route('mensualidades.update', mensualidadAAnular.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAnularModal.value = false;
            alert('Pago anulado exitosamente.');
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
};

const openVerAnuladoModal = (m: any) => {
    mensualidadVerAnulada.value = m;
    showViewAnuladoModal.value = true;
};

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
};</script>

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

                <!-- Super Filtros y Caja -->
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

                    <!-- Inline performance switch helper -->
                    <div class="mt-5 pt-4 border-t border-gray-150 flex items-center justify-between gap-4 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-500 animate-pulse" v-if="mensualidades.length === 0"></span>
                            <span class="text-xs text-gray-500 font-bold">
                                {{ mensualidades.length === 0 ? 'Carga optimizada: listado oculto inicialmente para acelerar el sistema.' : 'Listado dinámico cargado exitosamente.' }}
                            </span>
                        </div>
                        <label class="inline-flex items-center gap-2.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 px-4 py-2 rounded-xl cursor-pointer transition select-none">
                            <input type="checkbox" v-model="cargarTodosSwitch" class="h-4.5 w-4.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 cursor-pointer">
                            <span class="text-xs font-black text-gray-700">Cargar listado completo de mensualidades</span>
                        </label>
                    </div>

                </div>

                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                    <div v-if="mensualidades.length === 0" class="py-20 px-8 text-center max-w-lg mx-auto">
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
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estudiante</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Mes</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Monto</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="m in filteredMensualidades" :key="m.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ m.registro_internado?.estudiante?.persona?.nombre }} {{ m.registro_internado?.estudiante?.persona?.ap_paterno }}</div>
                                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                                            <span class="text-[10px] text-gray-500 font-bold bg-gray-100 px-2 py-0.5 rounded-md border border-gray-200/60">CI: {{ m.registro_internado?.estudiante?.persona?.ci }}</span>
                                            <span class="text-[10px] text-teal-700 font-extrabold bg-teal-50 px-2 py-0.5 rounded-md border border-teal-100">📅 {{ m.registro_internado?.gestion?.nombre_gestion }}</span>
                                            <span v-if="m.registro_internado?.curso" class="text-[10px] text-indigo-700 font-extrabold bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">🎓 {{ m.registro_internado?.curso?.nombre }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ m.mes }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        Bs. {{ m.total }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="m.estado === 'Pagado'" class="inline-flex flex-col">
                                            <span class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800 self-start">Pagado ({{ m.metodo_pago }})</span>
                                            <span class="text-[10px] text-gray-500 font-bold mt-1">📅 {{ m.fecha_pago }}</span>
                                        </span>
                                        <span v-else-if="m.estado === 'Anulado'" class="inline-flex flex-col">
                                            <span class="px-3 py-1 text-xs font-bold rounded-full bg-rose-100 text-rose-800 self-start">Anulado</span>
                                            <span class="text-[10px] text-rose-500 font-bold mt-1">Ver motivo 👁️</span>
                                        </span>
                                        <span v-else class="px-3 py-1 text-xs font-bold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <!-- Pago Pendiente -->
                                        <template v-if="m.estado === 'Pendiente' || m.estado === 'Pendiente_Verificacion'">
                                            <button @click="openPagarModal(m)" class="inline-flex items-center gap-1 px-3 py-1.5 bg-teal-50 hover:bg-teal-100 text-teal-600 rounded-xl text-xs font-bold transition">
                                                💵 Cobrar
                                            </button>
                                            <button @click="deleteMensualidad(m.id)" class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-xs font-bold transition">
                                                ❌ Eliminar
                                            </button>
                                        </template>
                                        
                                        <!-- Pago Realizado (Pagado) -->
                                        <template v-else-if="m.estado === 'Pagado'">
                                            <button @click="openAnularModal(m)" class="inline-flex items-center gap-1 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-bold transition">
                                                🚫 Anular
                                            </button>
                                        </template>
                                        
                                        <!-- Pago Anulado -->
                                        <template v-else-if="m.estado === 'Anulado'">
                                            <button @click="openVerAnuladoModal(m)" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-xl text-xs font-bold transition">
                                                👁️ Ver Motivo
                                            </button>
                                        </template>
                                    </td>
                                </tr>
                                <tr v-if="filteredMensualidades.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">No se encontraron registros de mensualidades.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Generar -->
                <div v-if="showGenerarModal" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="showGenerarModal = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                            <form @submit.prevent="submitGenerar">
                                <div class="bg-white px-6 pt-6 pb-4">
                                    <h3 class="text-xl font-bold text-gray-900 mb-6">Generación Masiva de Deudas</h3>
                                    <p class="text-sm text-gray-500 mb-4">Seleccione a los estudiantes activos a los que desea generar la deuda.</p>
                                    
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Deuda</label>
                                        <select v-model="formGenerar.concepto_tipo" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                                            <option value="mes">Mensualidad Regular (Mes)</option>
                                            <option value="personalizado">Concepto Personalizado (Otro)</option>
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 mb-4">
                                        <div v-if="formGenerar.concepto_tipo === 'mes'">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Mes a Generar</label>
                                            <select v-model="formGenerar.mes" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                                                <option v-for="mes in mesesDisponibles" :key="mes" :value="mes">{{ mes }}</option>
                                            </select>
                                        </div>
                                        <div v-else>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Concepto Específico</label>
                                            <input type="text" v-model="formGenerar.concepto_personalizado" placeholder="ej. Aporte de Aseo, Material" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto (Bs.)</label>
                                            <input type="number" v-model="formGenerar.monto" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Asignar a Estudiantes Específicos:</label>
                                        <input type="text" v-model="searchStudentInModal" placeholder="🔍 Buscar estudiante por Nombre o C.I..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-xs py-1.5 px-3 bg-slate-50 mb-3">
                                    </div>

                                    <div class="border rounded-lg max-h-60 overflow-y-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50 sticky top-0">
                                                <tr>
                                                    <th class="px-4 py-2 text-left">
                                                        <input type="checkbox" v-model="seleccionarTodos" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                                    </th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estudiante</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-for="est in filteredEstudiantesActivos" :key="est.id" class="hover:bg-gray-50">
                                                    <td class="px-4 py-2">
                                                        <input type="checkbox" :value="est.id" v-model="formGenerar.registro_internado_ids" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-gray-900">
                                                        {{ est.estudiante?.persona?.nombre }} {{ est.estudiante?.persona?.ap_paterno }} <span class="text-xs text-gray-400 font-medium">(CI: {{ est.estudiante?.persona?.ci }})</span>
                                                    </td>
                                                </tr>
                                                <tr v-if="!filteredEstudiantesActivos || filteredEstudiantesActivos.length === 0">
                                                    <td colspan="2" class="px-4 py-4 text-center text-sm text-gray-500">No se encontraron estudiantes coincidentes.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="text-xs text-red-600 mt-2 font-medium" v-if="formGenerar.errors.mes">{{ formGenerar.errors.mes }}</p>
                                    <p class="text-xs text-red-600 mt-2 font-medium" v-if="formGenerar.errors.registro_internado_ids">{{ formGenerar.errors.registro_internado_ids }}</p>
                                </div>
                                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse rounded-b-2xl">
                                    <button type="submit" :disabled="formGenerar.processing" class="w-full inline-flex justify-center rounded-lg shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 sm:ml-3 sm:w-auto sm:text-sm">
                                        {{ formGenerar.processing ? 'Generando...' : 'Generar' }}
                                    </button>
                                    <button @click="showGenerarModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Registrar Pago -->
                <div v-if="showPagarModal" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="showPagarModal = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                            <form @submit.prevent="submitPagar">
                                <div class="bg-white px-6 pt-6 pb-4">
                                    <h3 class="text-xl font-bold text-gray-900 mb-6">Registrar Pago</h3>
                                    <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                                        <p class="text-sm font-medium text-gray-900">{{ mensualidadSeleccionada?.registro_internado?.estudiante?.persona?.nombre }} {{ mensualidadSeleccionada?.registro_internado?.estudiante?.persona?.ap_paterno }}</p>
                                        <p class="text-xs text-gray-500">Mes: {{ mensualidadSeleccionada?.mes }} | Monto: Bs. {{ mensualidadSeleccionada?.total }}</p>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago</label>
                                            <select v-model="formPagar.metodo_pago" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                                <option value="Efectivo">Efectivo Físico</option>
                                                <option value="QR">Transferencia Bancaria / QR</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Pago</label>
                                            <input type="date" v-model="formPagar.fecha_pago" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm font-semibold text-gray-700">
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse rounded-b-2xl">
                                    <button type="submit" :disabled="formPagar.processing" class="w-full inline-flex justify-center rounded-lg shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 sm:ml-3 sm:w-auto sm:text-sm">
                                        {{ formPagar.processing ? 'Registrando...' : 'Confirmar Pago' }}
                                    </button>
                                    <button @click="showPagarModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Anular Pago -->
                <div v-if="showAnularModal" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="showAnularModal = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100">
                            <form @submit.prevent="submitAnular">
                                <div class="bg-white px-6 pt-6 pb-4">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="p-2 bg-red-100 text-red-700 rounded-xl">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        </div>
                                        <h3 class="text-lg font-black text-slate-800 leading-tight">Anular Transacción</h3>
                                    </div>
                                    
                                    <div class="mb-4 p-4 bg-red-50 rounded-2xl border border-red-100">
                                        <p class="text-sm font-bold text-red-900">{{ mensualidadAAnular?.registro_internado?.estudiante?.persona?.nombre }} {{ mensualidadAAnular?.registro_internado?.estudiante?.persona?.ap_paterno }}</p>
                                        <p class="text-xs text-red-700 font-medium">Mes: {{ mensualidadAAnular?.mes }} | Monto original: Bs. {{ mensualidadAAnular?.total }}</p>
                                    </div>

                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Motivo de Anulación</label>
                                            <textarea v-model="formAnular.motivo_anulacion" rows="3" required placeholder="Escriba detalladamente el motivo de la anulación..." class="w-full rounded-xl border-gray-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm font-medium text-slate-700"></textarea>
                                            <p v-if="formAnular.errors.motivo_anulacion" class="text-xs text-red-600 mt-1 font-bold">{{ formAnular.errors.motivo_anulacion }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Confirmar Contraseña de Admin</label>
                                            <input type="password" v-model="formAnular.password" required placeholder="Ingrese su contraseña actual..." class="w-full rounded-xl border-gray-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm font-semibold text-slate-700">
                                            <p v-if="formAnular.errors.password" class="text-xs text-red-600 mt-1 font-bold">{{ formAnular.errors.password }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse rounded-b-3xl border-t border-slate-100">
                                    <button type="submit" :disabled="formAnular.processing" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-red-600 text-sm font-bold text-white hover:bg-red-700 sm:ml-3 sm:w-auto transition">
                                        {{ formAnular.processing ? 'Anulando...' : 'Anular Pago' }}
                                    </button>
                                    <button @click="showAnularModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-200 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 sm:mt-0 sm:ml-3 sm:w-auto transition">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Ver Motivo de Anulación -->
                <div v-if="showViewAnuladoModal" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="showViewAnuladoModal = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100">
                            <div class="bg-white px-6 pt-6 pb-4">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="p-2 bg-indigo-100 text-indigo-700 rounded-xl">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </div>
                                    <h3 class="text-lg font-black text-slate-800 leading-tight">Detalles de Anulación</h3>
                                </div>
                                
                                <div class="mb-4 p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-2">
                                    <p class="text-sm font-bold text-slate-800">
                                        <span class="text-slate-400 font-semibold uppercase text-xs block">Estudiante</span>
                                        {{ mensualidadVerAnulada?.registro_internado?.estudiante?.persona?.nombre }} {{ mensualidadVerAnulada?.registro_internado?.estudiante?.persona?.ap_paterno }}
                                    </p>
                                    <p class="text-sm font-bold text-slate-800">
                                        <span class="text-slate-400 font-semibold uppercase text-xs block">Mes y Monto</span>
                                        Mes: {{ mensualidadVerAnulada?.mes }} &mdash; Bs. {{ mensualidadVerAnulada?.total }}
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <div class="p-4 bg-rose-50 rounded-2xl border border-rose-100">
                                        <span class="text-rose-500 font-black uppercase text-[10px] tracking-widest block mb-1">Motivo de Anulación Oficial</span>
                                        <p class="text-sm font-semibold text-rose-900 italic">
                                            "{{ mensualidadVerAnulada?.motivo_anulacion || 'Sin motivo especificado' }}"
                                        </p>
                                    </div>
                                    <div class="text-xs text-slate-400 font-semibold">
                                        Anulado el: {{ new Date(mensualidadVerAnulada?.updated_at).toLocaleDateString() }} a las {{ new Date(mensualidadVerAnulada?.updated_at).toLocaleTimeString() }}
                                    </div>
                                </div>
                            </div>
                            <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse rounded-b-3xl border-t border-slate-100">
                                <button @click="showViewAnuladoModal = false" type="button" class="w-full inline-flex justify-center rounded-xl border border-slate-200 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 sm:w-auto transition">
                                    Cerrar Vista
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
