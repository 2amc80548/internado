<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
    role: string;
    estudiante: any;
    registroActual: any;
    configuracion: any;
}>();

const activeTab = ref('resumen');

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
const mesSeleccionado = ref('Febrero');

const submitBoletin = () => {
    boletinForm.post(route('boletines.store'), {
        preserveScroll: true,
        onSuccess: () => {
            boletinForm.reset('ruta_archivo');
            alert('Boletín subido exitosamente y enviado a revisión.');
        },
    });
};

const notifyWhatsApp = () => {
    const mensaje = `Hola, soy ${props.estudiante?.persona?.nombre} ${props.estudiante?.persona?.ap_paterno} (CI: ${props.estudiante?.persona?.ci}). Realicé el pago de mi mensualidad de ${mesSeleccionado.value}.`;
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
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                Mi Portal
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Welcome Section -->
                <div class="mb-8 p-8 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl shadow-lg text-white relative overflow-hidden">
                    <div class="relative z-10">
                        <h3 class="text-3xl font-bold mb-2">¡Hola, {{ estudiante?.persona?.nombre }}!</h3>
                        <p class="text-emerald-100 text-lg">Bienvenido a tu portal. Aquí puedes ver tu información, subir tus notas y comprobantes de pago.</p>
                        <p v-if="registroActual" class="mt-4 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white text-teal-800 shadow-sm">
                            Gestión Activa: {{ registroActual.gestion.nombre_gestion }}
                        </p>
                    </div>
                    <!-- Decorative background -->
                    <div class="absolute right-0 top-0 opacity-10 transform translate-x-1/3 -translate-y-1/4">
                        <svg width="300" height="300" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                </div>

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
                    <!-- Nav Tabs -->
                    <div class="mb-6 border-b border-gray-200 overflow-x-auto scrollbar-none">
                    <nav class="-mb-px flex space-x-6 md:space-x-8 min-w-max" aria-label="Tabs">
                        <button @click="activeTab = 'resumen'" :class="activeTab === 'resumen' ? 'border-teal-500 text-teal-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg transition-all duration-300">
                            📊 Mi Resumen
                        </button>
                        <button @click="activeTab = 'finanzas'" :class="activeTab === 'finanzas' ? 'border-teal-500 text-teal-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg transition-all duration-300">
                            💰 Pagar Mensualidad
                        </button>
                        <button @click="activeTab = 'academico'" :class="activeTab === 'academico' ? 'border-teal-500 text-teal-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg transition-all duration-300">
                            📚 Subir Boletines
                        </button>
                        <button @click="activeTab = 'trayectoria'" :class="activeTab === 'trayectoria' ? 'border-teal-500 text-teal-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg transition-all duration-300">
                            🎓 Mi Trayectoria
                        </button>
                    </nav>
                </div>

                <div v-if="!registroActual" class="bg-white p-12 text-center rounded-3xl shadow-xl border border-gray-100 mb-8">
                    <div class="w-24 h-24 mx-auto bg-amber-100 text-amber-500 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 mb-2">Inscripción en Proceso</h3>
                    <p class="text-gray-500 max-w-lg mx-auto">Tu cuenta ha sido aprobada para ingresar al sistema, pero el administrador aún no te ha asignado un Curso ni una Gestión Académica. Por favor, comunícate con la administración o vuelve a revisar más tarde.</p>
                </div>

                <div v-else>
                    <!-- TAB ACADEMICO -->
                    <div v-show="activeTab === 'academico'" class="animate-fade-in-up">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Mis Boletines Escoares</h3>
                            <p class="text-gray-600 mb-8">La gestión actual está dividida en <strong>{{ cantidadPeriodos }} {{ tipoPeriodo }}s</strong>. Sube una foto clara o un PDF de tus notas en el periodo que corresponda.</p>

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
                                        <label :for="`boletin-${periodo}`" class="mt-4 cursor-pointer flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg bg-white hover:bg-gray-50 transition">
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                                <p class="text-sm text-gray-500 font-medium">Subir Archivo</p>
                                                <p class="text-xs text-gray-400 mt-1">PDF, JPG (Max 5MB)</p></div>
                                            <input :id="`boletin-${periodo}`" type="file" class="hidden" accept=".pdf,image/*" @change="handleBoletinUpload($event, periodo)" :disabled="boletinForm.processing" />
                                        </label>
                                        <div v-if="boletinForm.processing && boletinForm.numero_periodo === periodo" class="mt-2 text-sm text-teal-600 font-medium">Subiendo...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB FINANZAS -->
                    <div v-show="activeTab === 'finanzas'" class="animate-fade-in-up">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                            <!-- Instrucciones y QR -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden lg:col-span-1 p-6 flex flex-col items-center text-center">
                                <h3 class="text-lg font-bold text-gray-800 mb-2">QR Oficial de Pagos</h3>
                                <p class="text-sm text-gray-500 mb-4">Paga desde la App de tu Banco Escaneando este código.</p>
                                <div class="w-full bg-gray-50 rounded-xl border-2 border-gray-100 flex items-center justify-center mb-4 overflow-hidden p-2">
                                    <img v-if="configuracion?.ruta_qr_pagos" :src="`/storage/${configuracion.ruta_qr_pagos}`" alt="QR Pagos" class="w-full max-w-[200px] h-auto object-contain rounded-lg">
                                    <div v-else class="text-gray-400 py-10">QR no configurado</div>
                                </div>
                                <p class="text-xs text-teal-700 font-medium bg-teal-50 px-3 py-2 rounded-lg w-full">Puedes descargar la imagen manteniendo presionado sobre ella.</p>
                            </div>

                            <!-- Notificar Pago -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden lg:col-span-1">
                                <div class="px-6 py-5 border-b border-gray-100 bg-teal-50/50">
                                    <h3 class="text-lg font-bold text-teal-800">Notificar a la Encargada</h3>
                                </div>
                                <div class="p-6">
                                    <p class="text-sm text-gray-600 mb-4">Después de realizar la transferencia, selecciona el mes que estás pagando y presiona el botón para enviar tu comprobante directamente al WhatsApp de administración.</p>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Mes que estás pagando</label>
                                            <select v-model="mesSeleccionado" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                                <option v-for="mes in mesesDisponibles" :key="mes" :value="mes">{{ mes }}</option>
                                            </select>
                                        </div>
                                        
                                        <div class="pt-4">
                                            <button @click="notifyWhatsApp" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-[#25D366] hover:bg-[#128C7E] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#25D366] transition transform hover:scale-105">
                                                <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.052 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                                                Enviar Comprobante por WhatsApp
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Historial Pagos -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 lg:col-span-2">
                                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                    <h3 class="text-lg font-bold text-gray-800">Mi Historial de Mensualidades</h3>
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
                                                <tr v-for="pago in registroActual.mensualidades" :key="pago.id" class="border-t border-gray-50 hover:bg-gray-50/50 transition">
                                                    <td class="py-4 font-medium text-gray-900">{{ pago.mes }}</td>
                                                    <td class="py-4">Bs. {{ pago.monto }}</td>
                                                    <td class="py-4">
                                                        <span v-if="pago.estado === 'Pagado'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Verificado</span>
                                                        <span v-else-if="pago.estado === 'Pendiente_Verificacion'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">En Revisión</span>
                                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Pendiente</span>
                                                    </td>
                                                </tr>
                                                <tr v-if="!registroActual.mensualidades || registroActual.mensualidades.length === 0">
                                                    <td colspan="3" class="py-8 text-center text-gray-400">No tienes historial de pagos registrado aún.</td>
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
                            <!-- Datos Personales y Estado (Diseño Tipo Carnet) -->
                            <div class="lg:col-span-2 flex justify-center">
                                <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden w-full max-w-2xl relative">
                                    
                                    <!-- Cabecera Carnet -->
                                    <div class="bg-teal-700 h-24 flex items-center justify-between px-6">
                                        <div class="text-white flex items-center gap-3">
                                            <div class="p-2 bg-white/20 rounded-lg">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                            </div>
                                            <div>
                                                <h4 class="font-black text-xl tracking-widest uppercase">INTERNADO</h4>
                                                <p class="text-teal-200 text-xs font-medium tracking-widest">CREDENCIAL DE ESTUDIANTE</p>
                                            </div>
                                        </div>
                                        <div class="text-right text-white">
                                            <p class="text-xs font-bold opacity-80 uppercase tracking-widest">Gestión</p>
                                            <p class="text-xl font-black">{{ registroActual.gestion?.nombre_gestion || '2026' }}</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Cuerpo Carnet -->
                                    <div class="p-6 relative">
                                        <!-- Marca de agua de fondo -->
                                        <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none">
                                            <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z" /><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" /></svg>
                                        </div>
                                        
                                        <div class="flex flex-col sm:flex-row gap-6 relative z-10">
                                                <!-- Foto Column -->
                                            <div class="flex flex-col items-center shrink-0">
                                                <div class="w-32 h-40 bg-gray-100 rounded-lg border-2 border-gray-300 shadow-inner flex items-center justify-center overflow-hidden relative group">
                                                    <img v-if="estudiante.ruta_foto" :src="estudiante.ruta_foto.startsWith('http') || estudiante.ruta_foto.startsWith('/') ? estudiante.ruta_foto : '/storage/' + estudiante.ruta_foto" class="w-full h-full object-cover">
                                                    <svg v-else class="w-20 h-20 text-gray-300" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                                    
                                                    <label class="absolute inset-0 bg-black/60 text-white flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer transition-all duration-300 backdrop-blur-xs">
                                                        <svg class="w-8 h-8 mb-1 transform group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                        <span class="text-xs font-black text-center tracking-wider px-2 uppercase">{{ estudiante.ruta_foto ? 'Cambiar Foto' : 'Subir Foto' }}</span>
                                                        <input type="file" class="hidden" accept="image/*" @change="handleFotoChange" :disabled="fotoForm.processing">
                                                    </label>
                                                </div>
                                                <span v-if="fotoForm.processing" class="text-xs text-teal-600 font-bold mt-1">Subiendo...</span>
                                                <span v-if="fotoForm.errors.foto" class="text-xs text-red-600 font-bold mt-1 text-center">{{ fotoForm.errors.foto }}</span>
 
                                                <span :class="{
                                                    'bg-green-100 text-green-800 border-green-200': registroActual.estado_anual === 'Cursando' || registroActual.estado_anual === 'Aprobado',
                                                    'bg-red-100 text-red-800 border-red-200': registroActual.estado_anual === 'Retirado' || registroActual.estado_anual === 'Reprobado'
                                                }" class="mt-4 px-4 py-1.5 border text-xs leading-5 font-black uppercase tracking-wider rounded-full shadow-sm">
                                                    {{ registroActual.estado_anual }}
                                                </span>
                                            </div>
 
                                            <!-- Data Column -->
                                            <div class="flex-1 flex flex-col justify-center space-y-4">
                                                <div>
                                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Apellidos</p>
                                                    <p class="text-xl font-black text-gray-800 leading-none uppercase">{{ estudiante.persona.ap_paterno }} {{ estudiante.persona.ap_materno }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Nombres</p>
                                                    <p class="text-xl font-bold text-gray-800 leading-none uppercase">{{ estudiante.persona.nombre }}</p>
                                                </div>
                                                
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                                    <div>
                                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Documento CI</p>
                                                        <p class="text-sm font-bold text-gray-700">{{ estudiante.persona.ci }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Curso / Grado</p>
                                                        <p class="text-sm font-bold text-teal-700">{{ registroActual.curso?.nombre || 'S/N' }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Comunidad</p>
                                                        <p class="text-sm font-bold text-gray-700">{{ estudiante.comunidad?.nombre || 'S/N' }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Tutor Apoderado</p>
                                                        <p class="text-sm font-bold text-gray-700 truncate">{{ estudiante.tutor?.persona?.nombre }} {{ estudiante.tutor?.persona?.ap_paterno }}</p>
                                                    </div>
                                                </div>
                                            </div>                                   </div>
                                        </div>
                                        
                                        <!-- Footer Line -->
                                        <div class="mt-6 pt-4 border-t-2 border-dashed border-gray-200 flex justify-between items-center text-xs">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center border border-gray-200">
                                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-800">Ubicación</p>
                                                    <p class="text-gray-500 font-medium">{{ registroActual.pabellon }} - Cama {{ registroActual.cama }}</p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-gray-800">Uso Exclusivo</p>
                                                <p class="text-gray-500 font-medium">Personal e Intransferible</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="h-2 bg-gradient-to-r from-teal-500 to-blue-500"></div>
                                </div>
                            
                            <!-- Alerta de Deudas y Progreso -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 lg:col-span-1 flex flex-col justify-center">
                                <h3 class="text-lg font-bold text-gray-900 mb-4 text-center">Progreso de Mensualidades</h3>
                                
                                <div class="relative pt-1">
                                    <div class="flex mb-2 items-center justify-between">
                                        <div>
                                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-teal-600 bg-teal-200">
                                                Pagado
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs font-semibold inline-block text-teal-600">
                                                {{ (registroActual.mensualidades?.filter(m => m.estado === 'Pagado').length || 0) }} / 10 meses
                                            </span>
                                        </div>
                                    </div>
                                    <div class="overflow-hidden h-3 mb-4 text-xs flex rounded bg-teal-100">
                                        <div :style="`width: ${((registroActual.mensualidades?.filter(m => m.estado === 'Pagado').length || 0) / 10) * 100}%`" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-teal-500"></div>
                                    </div>
                                </div>
                                
                                <div v-if="(registroActual.mensualidades?.filter(m => m.estado === 'Pagado').length || 0) < Math.max(1, new Date().getMonth() - 1)" class="mt-4 p-4 bg-red-50 rounded-xl border border-red-100 text-center">
                                    <svg class="w-8 h-8 text-red-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <p class="text-sm font-bold text-red-800">Alerta de Atraso</p>
                                    <p class="text-xs text-red-600 mt-1">Registras menos pagos que los meses transcurridos del año. Por favor, regulariza tu situación.</p>
                                </div>
                                <div v-else class="mt-4 p-4 bg-green-50 rounded-xl border border-green-100 text-center">
                                    <svg class="w-8 h-8 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <p class="text-sm font-bold text-green-800">¡Al Día!</p>
                                    <p class="text-xs text-green-600 mt-1">No presentas deudas atrasadas detectadas en el sistema.</p>
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
                                        <tr v-for="incidencia in registroActual.incidencias" :key="incidencia.id" class="border-t border-gray-50">
                                            <td class="py-4">{{ new Date(incidencia.fecha_incidencia).toLocaleDateString() }}</td>
                                            <td class="py-4">
                                                <span :class="incidencia.gravedad === 'Leve' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                    {{ incidencia.gravedad }}
                                                </span>
                                            </td>
                                            <td class="py-4 truncate max-w-xs">{{ incidencia.descripcion }}</td>
                                        </tr>
                                        <tr v-if="!registroActual.incidencias || registroActual.incidencias.length === 0">
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
</style>
