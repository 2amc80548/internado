<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps<{
    role: string;
    estudiante: any;
    registroActual: any;
    configuracion: any;
}>();

const page = usePage();
const activeTab = computed(() => {
    const url = new URL(page.url, window.location.origin);
    const tabParam = url.searchParams.get('tab');
    return tabParam && ['resumen', 'finanzas', 'academico', 'trayectoria'].includes(tabParam) 
        ? tabParam 
        : 'resumen';
});

const selectedRegId = ref(props.registroActual?.id || null);

const selectedRegistro = computed(() => {
    if (!selectedRegId.value) return props.registroActual;
    const list = props.estudiante?.registros_internado || props.estudiante?.registrosInternado || [];
    return list.find((r: any) => r.id === selectedRegId.value) || props.registroActual;
});

const isSelectedRegActive = computed(() => {
    return selectedRegistro.value?.id === props.registroActual?.id;
});

// Computed values
const cantidadPeriodos = computed(() => {
    return props.registroActual?.gestion?.cantidad_periodos || 3;
});

const tipoPeriodo = computed(() => {
    return props.registroActual?.gestion?.tipo_periodo_academico || 'Trimestre';
});

// Formularios
const boletinForm = useForm({
    registro_internado_id: props.registroActual?.id,
    numero_periodo: 1,
    ruta_archivo: null as File | null,
});

const fotoForm = useForm({
    foto: null as File | null,
});

const mesesDisponibles = ['Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre'];

// Checklist de mensualidades
const mensualidadesPendientes = computed(() => {
    const list = selectedRegistro.value?.mensualidades || [];
    return list.filter((m: any) => m.estado === 'Pendiente');
});

const mesesSeleccionados = ref<any[]>([]);

watch(selectedRegId, () => {
    mesesSeleccionados.value = [];
});

const totalAPagar = computed(() => {
    return mesesSeleccionados.value.reduce((sum, m) => sum + Number(m.monto), 0);
});

const deudaTotalGestion = computed(() => {
    const list = selectedRegistro.value?.mensualidades || [];
    return list
        .filter((m: any) => m.estado === 'Pendiente' || m.estado === 'Pendiente_Verificacion')
        .reduce((sum, m) => sum + Number(m.total), 0);
});

const submitBoletin = () => {
    boletinForm.registro_internado_id = selectedRegistro.value?.id;
    boletinForm.post(route('boletines.store'), {
        preserveScroll: true,
        onSuccess: () => {
            boletinForm.reset('ruta_archivo');
            alert('Boletín subido exitosamente y enviado a revisión.');
        },
    });
};

const notifyWhatsApp = () => {
    if (mesesSeleccionados.value.length === 0) {
        alert("Por favor, seleccione al menos un mes a pagar.");
        return;
    }
    
    const gestionNombre = selectedRegistro.value?.gestion?.nombre_gestion || '';
    const mesesNombres = mesesSeleccionados.value.map(m => m.mes).join(', ');
    
    const mensaje = `Hola, soy ${props.estudiante?.persona?.nombre} ${props.estudiante?.persona?.ap_paterno} (CI: ${props.estudiante?.persona?.ci}). Realicé el pago de mis mensualidades de la gestión ${gestionNombre} correspondientes a los meses: ${mesesNombres}. Monto total: Bs. ${totalAPagar.value}.`;
    const encodedMensaje = encodeURIComponent(mensaje);
    window.open(`https://wa.me/59163591312?text=${encodedMensaje}`, '_blank');
};

const handleFotoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        fotoForm.foto = target.files[0];
        submitFoto();
    }
};

const submitFoto = () => {
    fotoForm.post(route('estudiantes.foto', props.estudiante.id), {
        preserveScroll: true,
        onSuccess: () => {
            fotoForm.reset();
        }
    });
};

const handleBoletinUpload = (event: Event, periodo: number) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        
        // Validación de Peso y Tipo
        if (file.size > 5 * 1024 * 1024) {
            alert("El archivo es demasiado pesado. El máximo permitido es 5MB.");
            target.value = '';
            return;
        }
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            alert("Solo se permiten archivos PDF, JPG o PNG.");
            target.value = '';
            return;
        }

        boletinForm.numero_periodo = periodo;
        boletinForm.ruta_archivo = file;
        submitBoletin();
    }
};

const getBoletinByPeriodo = (periodo: number) => {
    return props.registroActual?.boletines?.find((b: any) => b.numero_periodo === periodo);
};

const registrosOrdenados = computed(() => {
    const list = props.estudiante?.registros_internado || props.estudiante?.registrosInternado || [];
    return [...list].sort((a, b) => {
        const yearA = parseInt(a.gestion?.nombre_gestion || '0');
        const yearB = parseInt(b.gestion?.nombre_gestion || '0');
        return yearB - yearA; // Newest first
    });
});

const cursoIngreso = computed(() => {
    if (registrosOrdenados.value.length === 0) return 'Ninguno';
    return registrosOrdenados.value[registrosOrdenados.value.length - 1]?.curso?.nombre || 'Ninguno';
});

const totalAnios = computed(() => {
    return registrosOrdenados.value.length;
});
</script>

<template>
    <Head title="Portal del Estudiante" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-2xl text-gray-800 leading-tight">
                Mi Perfil
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- VISTA EXCLUSIVA PARA BACHILLERES -->
                <div v-if="estudiante?.estado_global === 'Bachiller'" class="space-y-8 animate-fade-in-up">
                    <!-- Banner de Felicitación Premium -->
                    <div class="p-8 bg-gradient-to-r from-teal-600 via-indigo-600 to-purple-600 rounded-3xl shadow-xl text-white relative overflow-hidden text-center sm:text-left">
                        <div class="relative z-10 max-w-3xl">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white mb-4 uppercase tracking-wider backdrop-blur-md">
                                🎓 Egresado Bachiller
                            </span>
                            <h3 class="text-4xl font-extrabold mb-4 leading-tight">¡Muchas Felicidades, {{ estudiante?.persona?.nombre }}!</h3>
                            <p class="text-teal-100 text-lg leading-relaxed mb-4">
                                Has culminado exitosamente tu formación escolar en el internado. Nos enorgullece haber sido parte de tu camino y te deseamos el mayor de los éxitos en esta nueva etapa de tu vida.
                            </p>
                            <div class="p-4 bg-white/10 border border-white/10 rounded-2xl text-sm text-teal-50 backdrop-blur-md flex items-start gap-3">
                                <span class="text-xl shrink-0">ℹ️</span>
                                <p class="text-left font-medium">
                                    Al haber egresado, tu cuenta de estudiante se encuentra en modo **Egresado (Inactivo)**. Tus accesos ordinarios para subir notas o reportar pagos mensuales han concluido. Si necesitas más información o copias de tu historial académico, por favor comunícate con la **Administración**.
                                </p>
                            </div>
                        </div>
                        <!-- Decorative elements -->
                        <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-1/4 translate-y-1/4 scale-150 pointer-events-none">
                            <span class="text-[200px]">🎓</span>
                        </div>
                    </div>

                    <!-- Datos Básicos y Trayectoria del Egresado -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Credencial Histórica -->
                        <div class="lg:col-span-1 flex justify-center">
                            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden w-full max-w-sm relative">
                                <div class="bg-indigo-700 h-20 flex items-center justify-between px-5">
                                    <div class="text-white flex items-center gap-2">
                                        <h4 class="font-black text-sm tracking-widest uppercase">INTERNADO</h4>
                                    </div>
                                    <span class="text-xs font-bold text-indigo-200 uppercase tracking-widest">Egresado</span>
                                </div>
                                <div class="p-6 text-center">
                                    <!-- Photo (No Edit Hover) -->
                                    <div class="w-28 h-36 mx-auto bg-gray-100 rounded-lg border border-gray-300 shadow-inner flex items-center justify-center overflow-hidden mb-4">
                                        <img v-if="estudiante.ruta_foto" :src="estudiante.ruta_foto.startsWith('http') || estudiante.ruta_foto.startsWith('/') ? estudiante.ruta_foto : '/storage/' + estudiante.ruta_foto" class="w-full h-full object-cover">
                                        <svg v-else class="w-16 h-16 text-gray-300" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    </div>

                                    <h5 class="text-lg font-black text-slate-800 uppercase leading-none mb-1">
                                        {{ estudiante.persona.nombre }}
                                    </h5>
                                    <h6 class="text-sm font-bold text-slate-500 uppercase mb-4">
                                        {{ estudiante.persona.ap_paterno }} {{ estudiante.persona.ap_materno }}
                                    </h6>

                                    <div class="border-t border-gray-100 pt-4 space-y-2 text-left text-xs text-slate-600">
                                        <div class="flex justify-between">
                                            <span class="text-slate-400 font-bold uppercase">CI:</span>
                                            <span class="font-bold text-slate-800">{{ estudiante.persona.ci }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-400 font-bold uppercase">Comunidad:</span>
                                            <span class="font-bold text-slate-800">{{ estudiante.comunidad?.nombre || 'S/N' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-400 font-bold uppercase">Año de Egreso:</span>
                                            <span class="font-bold text-teal-600 text-sm">{{ estudiante.año_egreso_bachiller || '2026' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="h-2 bg-gradient-to-r from-teal-500 to-indigo-500"></div>
                            </div>
                        </div>

                        <!-- Trayectoria de la Ficha Histórica -->
                        <div class="lg:col-span-2">
                            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                                <h4 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                                    <span>📜</span> Historial de Trayectoria en el Internado
                                </h4>
                                
                                <div v-if="registrosOrdenados.length === 0" class="py-12 text-center text-gray-400">
                                    No se registran datos históricos en el internado.
                                </div>
                                <div v-else class="relative border-l-2 border-teal-100 ml-4 md:ml-8 pl-6 md:pl-10 space-y-6 py-2">
                                    <div v-for="reg in registrosOrdenados" :key="reg.id" class="relative group">
                                        <span class="absolute -left-[31px] md:-left-[47px] top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-white border-2 border-teal-500 bg-teal-500">
                                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        </span>
                                        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 hover:shadow-md hover:bg-white hover:border-teal-200 transition-all duration-300">
                                            <div class="flex justify-between items-center gap-2 mb-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-black text-teal-700 bg-teal-50 px-2 py-0.5 rounded-lg border border-teal-100">
                                                        {{ reg.gestion?.nombre_gestion || 'S/N' }}
                                                    </span>
                                                    <h4 class="text-sm font-bold text-gray-800">{{ reg.curso?.nombre || 'Curso no Asignado' }}</h4>
                                                </div>
                                                <span class="bg-green-100 text-green-800 border-green-200 border px-2 py-0.5 text-[10px] font-black uppercase rounded-full">
                                                    {{ reg.estado_anual }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-gray-600">
                                                <span><strong>BTH Carrera:</strong> {{ reg.curso_bth?.carrera_tecnica?.nombre || 'Ninguna' }}</span>
                                                <span><strong>Habitación:</strong> {{ reg.pabellon || 'S/N' }} - Cama {{ reg.cama || 'S/N' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VISTA NORMAL DE ESTUDIANTE ACTIVO -->
                <div v-else>
                    <div v-if="!registroActual" class="bg-white p-12 text-center rounded-3xl shadow-xl border border-gray-100 mb-8">
                        <div class="w-24 h-24 mx-auto bg-amber-100 text-amber-500 rounded-full flex items-center justify-center mb-6">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 mb-2">Inscripción en Proceso</h3>
                        <p class="text-gray-500 max-w-lg mx-auto">Tu cuenta ha sido aprobada para ingresar al sistema, pero el administrador aún no te ha asignado un Curso ni una Gestión Académica. Por favor, comunícate con la administración o vuelve a revisar más tarde.</p>
                    </div>

                    <div v-else class="w-full space-y-8 animate-fade-in-up">
                        <!-- Right Tab Content Area -->
                        <div class="w-full">
                    <!-- TAB ACADEMICO -->
                    <div v-show="activeTab === 'academico'" class="animate-fade-in-up">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Mis Boletines Escolares de la Gestión {{ registroActual?.gestion?.nombre_gestion }}</h3>
                            <p class="text-gray-600 mb-8">Esta gestión está dividida en <strong>{{ cantidadPeriodos }} {{ tipoPeriodo }}s</strong>. Sube una foto clara o un PDF de tus notas en el periodo que corresponda.</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div v-for="periodo in cantidadPeriodos" :key="periodo" class="border border-gray-200 rounded-xl p-6 bg-gray-50 text-center hover:shadow-md transition">
                                    <h4 class="text-lg font-bold text-gray-800 mb-2">{{ tipoPeriodo }} {{ periodo }}</h4>

                                    <div v-if="getBoletinByPeriodo(periodo)">
                                        <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 flex flex-col items-center">
                                            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span class="font-semibold text-sm">Boletín Subido</span>
                                            <a :href="`/storage/${getBoletinByPeriodo(periodo).ruta_archivo}`" target="_blank" class="text-xs mt-2 underline hover:text-green-900">Ver Archivo</a>
                                        </div>
                                    </div>
                                    <div v-else>
                                        <template v-if="isSelectedRegActive">
                                            <label :for="`boletin-${periodo}`" class="mt-4 cursor-pointer flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg bg-white hover:bg-gray-50 transition">
                                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                                    <p class="text-sm text-gray-500 font-medium">Subir Archivo</p>
                                                    <p class="text-xs text-gray-400 mt-1">PDF, JPG (Max 5MB)</p>
                                                </div>
                                                <input :id="`boletin-${periodo}`" type="file" class="hidden" accept=".pdf,image/*" @change="handleBoletinUpload($event, periodo)" :disabled="boletinForm.processing" />
                                            </label>
                                            <div v-if="boletinForm.processing && boletinForm.numero_periodo === periodo" class="mt-2 text-sm text-teal-600 font-medium">Subiendo...</div>
                                        </template>
                                        <template v-else>
                                            <div class="mt-4 p-4 border border-slate-200 rounded-lg bg-slate-50 text-slate-400 text-xs font-semibold">
                                                No subido en esta gestión.
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- TAB FINANZAS -->
                    <div v-show="activeTab === 'finanzas'" class="animate-fade-in-up">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <!-- Indicador Deuda Consolidada -->
                            <div class="lg:col-span-3">
                                <div class="p-5 rounded-3xl flex flex-wrap items-center justify-between gap-4 border transition duration-300"
                                    :class="deudaTotalGestion > 0 ? 'bg-gradient-to-br from-amber-50 to-orange-50 border-amber-100 text-amber-900' : 'bg-gradient-to-br from-green-50 to-emerald-50 border-green-100 text-green-900'">
                                    <div class="flex items-center gap-3">
                                        <div class="p-3 rounded-2xl flex items-center justify-center text-xl shrink-0 shadow-sm" :class="deudaTotalGestion > 0 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'">
                                            💰
                                        </div>
                                        <div>
                                            <h4 class="font-black text-sm uppercase tracking-wider">Estado Financiero</h4>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-xs font-semibold text-slate-500">Seleccionar Gestión:</span>
                                                <select v-model="selectedRegId" class="bg-white hover:bg-slate-50 border border-gray-200 rounded-lg py-0.5 px-2 text-xs font-black text-slate-800 focus:ring-1 focus:ring-teal-500 cursor-pointer">
                                                    <option v-for="reg in registrosOrdenados" :key="reg.id" :value="reg.id">
                                                        Gestión {{ reg.gestion?.nombre_gestion }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-black block uppercase tracking-wider text-slate-400">Deuda Pendiente de Gestión</span>
                                        <span class="text-2xl font-black" :class="deudaTotalGestion > 0 ? 'text-amber-600' : 'text-green-600'">
                                            Bs. {{ deudaTotalGestion }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Instrucciones y QR -->
                            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden lg:col-span-1 p-6 flex flex-col items-center text-center">
                                <h3 class="text-lg font-bold text-gray-800 mb-2">QR Oficial de Pagos</h3>
                                <p class="text-sm text-gray-500 mb-4">Paga desde la App de tu Banco Escaneando este código.</p>
                                <div class="w-full bg-gray-50 rounded-2xl border-2 border-gray-100 flex items-center justify-center mb-4 overflow-hidden p-2">
                                    <img v-if="configuracion?.ruta_qr_pagos" :src="`/storage/${configuracion.ruta_qr_pagos}`" alt="QR Pagos" class="w-full max-w-[200px] h-auto object-contain rounded-lg">
                                    <div v-else class="text-gray-400 py-10">QR no configurado</div>
                                </div>
                                <p class="text-xs text-teal-700 font-medium bg-teal-50 px-3 py-2 rounded-lg w-full">Puedes descargar la imagen manteniendo presionado sobre ella.</p>
                            </div>

                            <!-- Notificar Pago Checklist -->
                            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden lg:col-span-1 flex flex-col justify-between">
                                <div>
                                    <div class="px-6 py-5 border-b border-gray-100 bg-teal-50/50">
                                        <h3 class="text-lg font-bold text-teal-800">Notificar a la Encargada</h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-xs text-gray-600 leading-relaxed mb-4">Realiza tu pago vía QR o transferencia y selecciona a continuación los meses que deseas pagar para reportar al WhatsApp administrativo:</p>
                                        
                                        <!-- Warning if not active year -->
                                        <div v-if="!isSelectedRegActive" class="mb-4 p-3 bg-amber-50 rounded-xl border border-amber-100 text-[11px] text-amber-800 font-semibold leading-relaxed">
                                            Estás viendo un registro histórico. Si deseas notificar mensualidades de la gestión actual, selecciona la gestión activa arriba.
                                        </div>

                                        <div class="space-y-3" v-if="isSelectedRegActive">
                                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Seleccionar Meses a Pagar</span>
                                            
                                            <div v-if="mensualidadesPendientes.length === 0" class="p-3 bg-green-50 border border-green-200 rounded-xl text-green-800 text-xs font-bold text-center">
                                                🎉 ¡Felicidades! No tienes mensualidades pendientes en esta gestión.
                                            </div>
                                            <div v-else class="max-h-48 overflow-y-auto border border-gray-100 rounded-xl divide-y bg-gray-50/50 p-2.5 space-y-1.5">
                                                <div v-for="pago in mensualidadesPendientes" :key="pago.id" class="flex items-center gap-2 p-1">
                                                    <input type="checkbox" :id="`chk-mes-${pago.id}`" :value="pago" v-model="mesesSeleccionados" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 cursor-pointer">
                                                    <label :for="`chk-mes-${pago.id}`" class="text-xs font-bold text-gray-700 cursor-pointer flex justify-between w-full">
                                                        <span>{{ pago.mes }}</span>
                                                        <span class="text-teal-600 font-black">Bs. {{ pago.monto }}</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="pt-3 border-t border-gray-100 flex justify-between items-center text-xs font-black text-gray-700" v-if="mesesSeleccionados.length > 0">
                                                <span>Total a Reportar:</span>
                                                <span class="text-sm text-teal-600">Bs. {{ totalAPagar }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6 pt-0" v-if="isSelectedRegActive && mensualidadesPendientes.length > 0">
                                    <button @click="notifyWhatsApp" :disabled="mesesSeleccionados.length === 0" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-2xl shadow-sm text-sm font-bold text-white bg-[#25D366] hover:bg-[#128C7E] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#25D366] disabled:opacity-50 disabled:cursor-not-allowed transition transform hover:scale-[1.02]">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.052 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                                        Enviar Comprobante por WhatsApp
                                    </button>
                                </div>
                            </div>

                            <!-- Historial Pagos -->
                            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 lg:col-span-2">
                                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                    <h3 class="text-lg font-bold text-gray-800">Mi Historial de Mensualidades (Gestión {{ selectedRegistro?.gestion?.nombre_gestion }})</h3>
                                </div>
                                <div class="p-6">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-left text-sm whitespace-nowrap">
                                            <thead class="uppercase tracking-wider text-gray-500 font-medium">
                                                <tr>
                                                    <th class="pb-4">Mes</th>
                                                    <th class="pb-4">Monto</th>
                                                    <th class="pb-4">Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-gray-600">
                                                <tr v-for="pago in selectedRegistro?.mensualidades" :key="pago.id" class="border-t border-gray-50 hover:bg-gray-50/50 transition">
                                                    <td class="py-4 font-medium text-gray-900">{{ pago.mes }}</td>
                                                    <td class="py-4">Bs. {{ pago.monto }}</td>
                                                    <td class="py-4">
                                                        <span v-if="pago.estado === 'Pagado'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Verificado</span>
                                                        <span v-else-if="pago.estado === 'Pendiente_Verificacion'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">En Revisión</span>
                                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Pendiente</span>
                                                    </td>
                                                </tr>
                                                <tr v-if="!selectedRegistro?.mensualidades || selectedRegistro?.mensualidades.length === 0">
                                                    <td colspan="3" class="py-8 text-center text-gray-400">No tienes historial de pagos registrado aún en esta gestión.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB RESUMEN (DASHBOARD) -->
                    <div v-show="activeTab === 'resumen'" class="animate-fade-in-up">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <!-- Perfil de Estudiante de Internado Premium -->
                            <div class="lg:col-span-3 space-y-6">
                                <!-- Banner Superior de Perfil / Cover -->
                                <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100 relative">
                                    <div class="h-36 bg-gradient-to-r from-teal-500 via-emerald-500 to-indigo-600 relative">
                                        <!-- Decorative dynamic design -->
                                        <div class="absolute inset-0 opacity-15 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-white via-transparent to-transparent"></div>
                                    </div>
                                    <div class="px-6 pb-6 relative pt-16 flex flex-col md:flex-row items-center md:items-end gap-6">
                                        <!-- Floating Profile Picture (Larger & Static) -->
                                        <div class="absolute -top-20 left-1/2 md:left-8 transform -translate-x-1/2 md:translate-x-0 w-40 h-40 bg-white p-1.5 rounded-full shadow-md overflow-hidden shrink-0">
                                            <div class="w-full h-full rounded-full overflow-hidden bg-gray-50 flex items-center justify-center border border-gray-200">
                                                <img v-if="estudiante.ruta_foto" :src="estudiante.ruta_foto.startsWith('http') || estudiante.ruta_foto.startsWith('/') ? estudiante.ruta_foto : '/storage/' + estudiante.ruta_foto" class="w-full h-full object-cover">
                                                <svg v-else class="w-16 h-16 text-gray-300" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                            </div>
                                        </div>
                                        
                                        <!-- Profile Info -->
                                        <div class="flex-1 text-center md:text-left mt-20 md:mt-0 pt-2 space-y-1.5">
                                            <div class="flex flex-col md:flex-row md:items-center gap-2">
                                                <h3 class="text-3xl font-black text-slate-800 uppercase tracking-tight leading-none">
                                                    {{ estudiante.persona.nombre }} {{ estudiante.persona.ap_paterno }} {{ estudiante.persona.ap_materno }}
                                                </h3>
                                                <span :class="{
                                                    'bg-green-100 text-green-800 border-green-200': registroActual?.estado_anual === 'Cursando' || registroActual?.estado_anual === 'Aprobado',
                                                    'bg-red-100 text-red-800 border-red-200': registroActual?.estado_anual === 'Retirado' || registroActual?.estado_anual === 'Reprobado'
                                                }" class="inline-flex self-center px-3.5 py-1 border text-xs font-black uppercase tracking-wider rounded-full shadow-xs">
                                                    {{ registroActual?.estado_anual || 'Cursando' }}
                                                </span>
                                            </div>
                                            <p class="text-gray-500 text-sm font-bold uppercase tracking-wider flex items-center justify-center md:justify-start gap-1.5">
                                                <span>🏫 Interno Habilitado</span> &bull; 
                                                <span class="text-teal-600 font-extrabold bg-teal-50 border border-teal-200 px-2.5 py-0.5 rounded-md">Gestión {{ registroActual?.gestion?.nombre_gestion }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Grid de Contenedores de Información -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <!-- Datos de Internación / Ubicación -->
                                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between hover:shadow-md transition duration-300">
                                        <div>
                                            <div class="flex items-center gap-2.5 mb-5 border-b border-gray-50 pb-3">
                                                <div class="p-2 bg-teal-50 text-teal-600 rounded-xl">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                                </div>
                                                <h4 class="font-black text-slate-800 text-sm uppercase tracking-wider">🏢 Mi Internación</h4>
                                            </div>
                                            <div class="space-y-4">
                                                <div>
                                                    <span class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-1">Pabellón Designado</span>
                                                    <span class="text-base font-black text-slate-800">{{ registroActual?.pabellon || 'Sin Asignar' }}</span>
                                                </div>
                                                <div>
                                                    <span class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-1">Número de Cama</span>
                                                    <span class="text-sm font-black text-teal-700 bg-teal-50 px-2.5 py-1 rounded-lg border border-teal-100 inline-block">Cama {{ registroActual?.cama || 'S/N' }}</span>
                                                </div>
                                                <div>
                                                    <span class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-1">Comunidad de Origen</span>
                                                    <span class="text-base font-black text-slate-800">{{ estudiante.comunidad?.nombre || 'S/N' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Datos Académicos -->
                                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between hover:shadow-md transition duration-300">
                                        <div>
                                            <div class="flex items-center gap-2.5 mb-5 border-b border-gray-50 pb-3">
                                                <div class="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                                </div>
                                                <h4 class="font-black text-slate-800 text-sm uppercase tracking-wider">📚 Nivel Académico</h4>
                                            </div>
                                            <div class="space-y-4">
                                                <div>
                                                    <span class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-1">Grado / Curso Regular</span>
                                                    <span class="text-base font-black text-slate-800">{{ registroActual?.curso?.nombre || 'S/N' }}</span>
                                                </div>
                                                <div>
                                                    <span class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-1">Carrera Técnica BTH</span>
                                                    <span class="text-xs font-black text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100 inline-block truncate max-w-full" :title="registroActual?.curso_bth?.carrera_tecnica?.nombre">
                                                        {{ registroActual?.curso_bth?.carrera_tecnica?.nombre || 'Ninguna Especialidad' }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-1">Años Cursados en Internado</span>
                                                    <span class="text-base font-black text-slate-800">{{ totalAnios }} {{ totalAnios === 1 ? 'Año' : 'Años' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Datos de Contacto y Apoderado -->
                                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between hover:shadow-md transition duration-300">
                                        <div>
                                            <div class="flex items-center gap-2.5 mb-5 border-b border-gray-50 pb-3">
                                                <div class="p-2 bg-blue-50 text-blue-600 rounded-xl">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                                </div>
                                                <h4 class="font-black text-slate-800 text-sm uppercase tracking-wider">📞 Familia y CI</h4>
                                            </div>
                                            <div class="space-y-4">
                                                <div>
                                                    <span class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-1">Cédula de Identidad CI</span>
                                                    <span class="text-base font-black text-slate-800">{{ estudiante.persona.ci }}</span>
                                                </div>
                                                <div>
                                                    <span class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-1">Tutor Apoderado</span>
                                                    <span class="text-base font-black text-slate-800 block truncate">{{ estudiante.tutor?.persona?.nombre }} {{ estudiante.tutor?.persona?.ap_paterno }}</span>
                                                </div>
                                                <div>
                                                    <span class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-1">Celular del Tutor</span>
                                                    <span class="text-base font-black text-slate-800">{{ estudiante.tutor?.persona?.celular || 'S/N' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fila de Alertas y Progreso en el Resumen -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Progreso de Mensualidades Widget -->
                                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                                        <h4 class="font-black text-slate-800 text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                                            <span>💳</span> Progreso de Mensualidades
                                        </h4>
                                        <div class="relative pt-1">
                                            <div class="flex mb-2 items-center justify-between">
                                                <div>
                                                    <span class="text-xs font-black inline-block py-1 px-2.5 uppercase rounded-full text-teal-600 bg-teal-50 border border-teal-100">
                                                        Al corriente
                                                    </span>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-sm font-black text-teal-600">
                                                        {{ (registroActual?.mensualidades?.filter(m => m.estado === 'Pagado').length || 0) }} / 10 meses
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="overflow-hidden h-3.5 mb-4 text-xs flex rounded-full bg-teal-100/50">
                                                <div :style="`width: ${((registroActual?.mensualidades?.filter(m => m.estado === 'Pagado').length || 0) / 10) * 100}%`" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-teal-500 rounded-full transition-all duration-500"></div>
                                            </div>
                                        </div>
                                        
                                        <div v-if="(registroActual?.mensualidades?.filter(m => m.estado === 'Pagado').length || 0) < Math.max(1, new Date().getMonth() - 1)" class="mt-4 p-4 bg-red-50 rounded-2xl border border-red-100 flex items-start gap-3">
                                            <span class="text-red-500 text-xl leading-none">⚠️</span>
                                            <div>
                                                <p class="text-sm font-black text-red-800">Alerta de Mensualidades Pendientes</p>
                                                <p class="text-xs text-red-600 mt-0.5 leading-relaxed">Presentas meses con cobro pendiente. Por favor, regulariza tu cuenta a la brevedad.</p>
                                            </div>
                                        </div>
                                        <div v-else class="mt-4 p-4 bg-green-50 rounded-2xl border border-green-100 flex items-start gap-3">
                                            <span class="text-green-500 text-xl leading-none">✨</span>
                                            <div>
                                                <p class="text-sm font-black text-green-800">¡Cuenta al Día!</p>
                                                <p class="text-xs text-green-600 mt-0.5 leading-relaxed">No registras atrasos de pago detectados en esta gestión.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Estado Académico / Conducta -->
                                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between">
                                        <div>
                                            <h4 class="font-black text-slate-800 text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                                                <span>🛡️</span> Estado Conductual y Faltas
                                            </h4>
                                            <div class="flex items-center gap-4">
                                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center shrink-0 bg-green-50 text-green-600 border border-green-100">
                                                    <span class="text-2xl font-black">{{ registroActual?.incidencias?.length || 0 }}</span>
                                                </div>
                                                <div>
                                                    <p class="text-base font-black text-slate-800">Faltas Registradas</p>
                                                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                                        {{ (registroActual?.incidencias?.length || 0) === 0 ? '¡Excelente conducta! No tienes faltas ni reportes en tu registro.' : 'Registras incidencias académicas o de conducta. Revisa tu historial en la administración.' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Historial Disciplinario (Debajo del perfil) -->
                        <div class="mt-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Historial Disciplinario</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-left text-sm whitespace-nowrap">
                                    <thead class="uppercase tracking-wider text-gray-500 font-medium">
                                        <tr>
                                            <th class="pb-4">Fecha</th>
                                            <th class="pb-4">Tipo</th>
                                            <th class="pb-4">Descripción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600">
                                        <tr v-for="incidencia in registroActual?.incidencias || []" :key="incidencia.id" class="border-t border-gray-50">
                                            <td class="py-4">{{ new Date(incidencia.fecha_incidencia).toLocaleDateString() }}</td>
                                            <td class="py-4">
                                                <span :class="incidencia.gravedad === 'Leve' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                    {{ incidencia.gravedad }}
                                                </span>
                                            </td>
                                            <td class="py-4 truncate max-w-xs">{{ incidencia.descripcion }}</td>
                                        </tr>
                                        <tr v-if="!registroActual?.incidencias || registroActual.incidencias.length === 0">
                                            <td colspan="3" class="py-8 text-center text-gray-400">¡Felicidades! No tienes faltas disciplinarias.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- TAB TRAYECTORIA ACADÉMICA -->
                    <div v-show="activeTab === 'trayectoria'" class="animate-fade-in-up">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-100 pb-6 mb-8 gap-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Historial y Trayectoria Académica</h3>
                                    <p class="text-sm text-gray-500">Cronología completa de tu paso por el internado.</p>
                                </div>
                                <div class="flex gap-4">
                                    <div class="bg-teal-50 px-4 py-2 rounded-xl text-center border border-teal-100">
                                        <p class="text-[10px] font-bold text-teal-600 uppercase tracking-widest">Curso de Ingreso</p>
                                        <p class="text-lg font-black text-teal-800">{{ cursoIngreso }}</p>
                                    </div>
                                    <div class="bg-blue-50 px-4 py-2 rounded-xl text-center border border-blue-100">
                                        <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Años en Internado</p>
                                        <p class="text-lg font-black text-blue-800">{{ totalAnios }} {{ totalAnios === 1 ? 'Año' : 'Años' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="registrosOrdenados.length === 0" class="py-12 text-center text-gray-400">
                                No se registran datos académicos anteriores.
                            </div>
                            <div v-else class="relative border-l-2 border-teal-100 ml-4 md:ml-8 pl-6 md:pl-10 space-y-8 py-2">
                                <div v-for="reg in registrosOrdenados" :key="reg.id" class="relative group">
                                    
                                    <!-- Dot Indicator -->
                                    <span class="absolute -left-[31px] md:-left-[47px] top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-white border-2 transition-all duration-300"
                                        :class="{
                                            'border-teal-500 ring-4 ring-teal-50': reg.estado_anual === 'Cursando',
                                            'border-green-500 bg-green-500': reg.estado_anual === 'Aprobado',
                                            'border-red-500 bg-red-500': reg.estado_anual === 'Reprobado',
                                            'border-gray-400 bg-gray-400': reg.estado_anual === 'Retirado'
                                        }">
                                        <span v-if="reg.estado_anual === 'Cursando'" class="h-2 w-2 rounded-full bg-teal-500 animate-ping"></span>
                                        <svg v-else-if="reg.estado_anual === 'Aprobado'" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        <svg v-else-if="reg.estado_anual === 'Reprobado'" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        <svg v-else class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 12H5"></path></svg>
                                    </span>

                                    <!-- Content Card -->
                                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6 hover:shadow-md hover:bg-white hover:border-teal-200 transition-all duration-300 transform group-hover:translate-x-1">
                                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-4">
                                            <div class="flex items-center gap-3">
                                                <span class="text-xl font-black text-gray-900 bg-teal-50 text-teal-700 px-3 py-1 rounded-xl border border-teal-100">
                                                    {{ reg.gestion?.nombre_gestion || 'S/N' }}
                                                </span>
                                                <h4 class="text-lg font-bold text-gray-800">{{ reg.curso?.nombre || 'Curso no Asignado' }}</h4>
                                            </div>
                                            <span :class="{
                                                'bg-teal-100 text-teal-800 border-teal-200': reg.estado_anual === 'Cursando',
                                                'bg-green-100 text-green-800 border-green-200': reg.estado_anual === 'Aprobado',
                                                'bg-red-100 text-red-800 border-red-200': reg.estado_anual === 'Reprobado',
                                                'bg-gray-100 text-gray-600 border-gray-200': reg.estado_anual === 'Retirado'
                                            }" class="px-3 py-1 border text-xs font-black uppercase tracking-wider rounded-full">
                                                {{ reg.estado_anual }}
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm text-gray-600">
                                            <div class="flex items-center gap-2">
                                                <span class="text-gray-400">🎓</span>
                                                <span><strong>Carrera BTH:</strong> {{ reg.curso_bth?.carrera_tecnica?.nombre || 'No Registra' }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-gray-400">🏢</span>
                                                <span><strong>Ubicación:</strong> Cama {{ reg.cama || 'S/N' }} ({{ reg.pabellon || 'S/N' }})</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-gray-400">📅</span>
                                                <span><strong>Periodos:</strong> {{ reg.gestion?.tipo_periodo_academico }} ({{ reg.gestion?.cantidad_periodos }})</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.4s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
