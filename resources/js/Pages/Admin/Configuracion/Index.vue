<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    configuracion: any;
}>();

const form = useForm({
    ruta_qr_pagos: null as File | null,
    ruta_logo: null as File | null,
    ruta_logo_login: null as File | null,
    nombre_sistema: props.configuracion?.nombre_sistema || 'INTERNADO',
    whatsapp_notificaciones: props.configuracion?.whatsapp_notificaciones || '',
    color_hexadecimal: props.configuracion?.color_hexadecimal || '#0d9488',
    permisos_encargada: props.configuracion?.permisos_encargada || [],
    modo_mensualidad_automatica: props.configuracion?.modo_mensualidad_automatica == 1,
    edicion_perfil_habilitada: props.configuracion?.edicion_perfil_habilitada == 1,
});

const handleQrUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        
        if (file.size > 5 * 1024 * 1024) {
            alert("La imagen es demasiado pesada. El máximo permitido es 5MB.");
            target.value = '';
            return;
        }
        if (!file.type.startsWith('image/')) {
            alert("Debe ser una imagen (JPG, PNG).");
            target.value = '';
            return;
        }

        form.ruta_qr_pagos = file;
    }
};

const handleLogoUpload = (event: Event, field: 'ruta_logo' | 'ruta_logo_login') => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        
        if (file.size > 5 * 1024 * 1024) {
            alert("La imagen es demasiado pesada. El máximo permitido es 5MB.");
            target.value = '';
            return;
        }
        if (!file.type.startsWith('image/')) {
            alert("Debe ser una imagen (JPG, PNG).");
            target.value = '';
            return;
        }

        form[field] = file;
    }
};

const submitForm = () => {
    form.post(route('configuracion.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('ruta_qr_pagos', 'ruta_logo', 'ruta_logo_login');
            alert('Configuración guardada exitosamente.');
        },
    });
};

const availablePermissions = [
    { value: 'estudiantes.index', name: 'Estudiantes', description: 'Registro, edición, y trayectoria escolar de estudiantes.' },
    { value: 'cursos.index', name: 'Grados Regulares', description: 'Gestión de paralelos y grados de educación regular.' },
    { value: 'cursos-bth.index', name: 'Cursos BTH', description: 'Gestión de especialidades técnicas de BTH.' },
    { value: 'mensualidades.index', name: 'Mensualidades (Pagos)', description: 'Gestión y verificación de cobros y deudas de estudiantes.' },
    { value: 'incidencias.index', name: 'Incidencias', description: 'Registro de faltas de conducta, sanciones e historial.' }
];
</script>

<template>
    <Head title="Configuración del Sistema" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                Configuración del Sistema
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Ajustes Globales</h3>
                            <p class="text-sm text-gray-500">Configura parámetros que afectan a todo el sistema.</p>
                        </div>
                    </div>

                    <div class="p-8">
                        <form @submit.prevent="submitForm" class="space-y-8">
                            
                            <!-- Aspecto y Datos del Sistema -->
                            <div>
                                <h4 class="text-md font-bold text-gray-900 mb-4 border-b pb-2">Aspecto y Datos del Sistema</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre del Sistema</label>
                                        <input type="text" v-model="form.nombre_sistema" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-900 focus:border-teal-500 focus:ring-teal-500 shadow-sm" placeholder="Ej. INTERNADO">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">WhatsApp para Notificaciones</label>
                                        <input type="text" v-model="form.whatsapp_notificaciones" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-900 focus:border-teal-500 focus:ring-teal-500 shadow-sm" placeholder="Ej. 59171234567">
                                        <span class="text-[10px] text-gray-400 block mt-1">Código de país seguido del celular sin espacios ni signos +.</span>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Color Temático de la Interfaz</label>
                                        <div class="flex items-center gap-3">
                                            <input type="color" v-model="form.color_hexadecimal" class="h-10 w-14 cursor-pointer rounded border border-gray-300 bg-transparent p-0 shrink-0">
                                            <input type="text" v-model="form.color_hexadecimal" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-teal-500 focus:ring-teal-500 shadow-sm font-mono uppercase" placeholder="#0D9488">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Logotipos del Sistema -->
                            <div class="pt-8 border-t border-gray-100">
                                <h4 class="text-md font-bold text-gray-900 mb-4 border-b pb-2">Logotipos del Sistema</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <!-- Logo Sistema -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2 font-medium">Logo de Navegación (Sidebar superior)</label>
                                        <div class="flex items-center gap-4">
                                            <label class="cursor-pointer bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition shadow-sm inline-flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                Subir Logo Sistema
                                                <input type="file" class="hidden" accept="image/*" @change="handleLogoUpload($event, 'ruta_logo')">
                                            </label>
                                            <span class="text-xs font-semibold text-teal-600" v-if="form.ruta_logo">Seleccionado</span>
                                        </div>
                                        <div class="mt-4 flex items-center justify-center bg-gray-50 border border-dashed border-gray-300 rounded-xl p-4 min-h-[140px]">
                                            <img v-if="configuracion?.ruta_logo" :src="`/storage/${configuracion.ruta_logo}`" alt="Logo Sistema" class="max-w-full max-h-24 object-contain rounded">
                                            <div v-else class="text-gray-400 text-xs">Sin logo personalizado (usa el predeterminado)</div>
                                        </div>
                                    </div>

                                    <!-- Logo Login -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2 font-medium">Logo de Pantalla de Inicio de Sesión</label>
                                        <div class="flex items-center gap-4">
                                            <label class="cursor-pointer bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition shadow-sm inline-flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                Subir Logo Login
                                                <input type="file" class="hidden" accept="image/*" @change="handleLogoUpload($event, 'ruta_logo_login')">
                                            </label>
                                            <span class="text-xs font-semibold text-teal-600" v-if="form.ruta_logo_login">Seleccionado</span>
                                        </div>
                                        <div class="mt-4 flex items-center justify-center bg-gray-50 border border-dashed border-gray-300 rounded-xl p-4 min-h-[140px]">
                                            <img v-if="configuracion?.ruta_logo_login" :src="`/storage/${configuracion.ruta_logo_login}`" alt="Logo Login" class="max-w-full max-h-24 object-contain rounded">
                                            <div v-else class="text-gray-400 text-xs">Sin logo personalizado (usa el predeterminado)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sección QR Pagos -->
                            <div class="pt-8 border-t border-gray-100">
                                <h4 class="text-md font-bold text-gray-900 mb-4 border-b pb-2">Pagos de Mensualidad</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">QR Oficial del Banco</label>
                                        <p class="text-xs text-gray-500 mb-4">Esta imagen se mostrará a los estudiantes en su portal cuando quieran registrar su mensualidad.</p>
                                        
                                        <label class="cursor-pointer bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition shadow-sm inline-flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            Seleccionar Nueva Imagen QR
                                            <input type="file" class="hidden" accept="image/*" @change="handleQrUpload">
                                        </label>
                                        <p class="mt-2 text-sm font-medium text-teal-600 truncate" v-if="form.ruta_qr_pagos">Archivo seleccionado: {{ (form.ruta_qr_pagos as File).name }}</p>
                                    </div>
                                    
                                    <div class="flex justify-center items-center bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl p-4 min-h-[200px]">
                                        <img v-if="configuracion?.ruta_qr_pagos" :src="`/storage/${configuracion.ruta_qr_pagos}`" alt="QR de Pagos" class="max-w-full max-h-48 object-contain rounded-lg">
                                        <div v-else class="text-center text-gray-400">
                                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            <span class="mt-2 block text-sm font-medium">Ningún QR subido</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Permisos Encargada -->
                            <div class="pt-8 border-t border-gray-100">
                                <h4 class="text-md font-bold text-gray-900 mb-2 pb-2 border-b">Permisos del Rol Encargada</h4>
                                <p class="text-xs text-gray-500 mb-6">Selecciona qué módulos del sidebar y endpoints del servidor tendrá permitidos visualizar y operar el rol de Encargada en el sistema.</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <label v-for="permission in availablePermissions" :key="permission.value" class="flex items-start bg-white p-4 rounded-xl border border-gray-200 shadow-sm cursor-pointer hover:bg-gray-50/50 transition select-none">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" :value="permission.value" v-model="form.permisos_encargada" class="h-5 w-5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 cursor-pointer">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <span class="font-bold text-gray-900">{{ permission.name }}</span>
                                            <p class="text-xs text-gray-500 mt-0.5">{{ permission.description }}</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Políticas y Permisos -->
                            <div class="pt-8 border-t border-gray-100">
                                <h4 class="text-md font-bold text-gray-900 mb-6 pb-2 border-b">Políticas y Controles Generales</h4>

                                <div class="space-y-6">
                                    <!-- Switch Mensualidad Automática -->
                                    <div class="flex items-center justify-between bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                        <div class="flex-1">
                                            <h5 class="text-sm font-bold text-gray-900">Generación Automática de Mensualidades</h5>
                                            <p class="text-xs text-gray-500 mt-1">Si está activado, el sistema generará deudas automáticamente cada 1er día del mes para los estudiantes activos.</p>
                                        </div>
                                        <div class="ml-4">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" v-model="form.modo_mensualidad_automatica" class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Switch Edición Perfil -->
                                    <div class="flex items-center justify-between bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                        <div class="flex-1">
                                            <h5 class="text-sm font-bold text-gray-900">Permitir Edición de Perfil</h5>
                                            <p class="text-xs text-gray-500 mt-1">Habilita que todos los estudiantes puedan editar sus datos personales desde su portal de forma temporal.</p>
                                        </div>
                                        <div class="ml-4">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" v-model="form.edicion_perfil_habilitada" class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t flex justify-end">
                                <button type="submit" :disabled="form.processing" class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-colors">
                                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
