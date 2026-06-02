<script setup lang="ts">
// Definición de las propiedades del componente
const props = defineProps<{
    show: boolean;
    estudiante: any;
}>();

// Definición de los eventos emitidos por el componente
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'edit', estudiante: any): void;
}>();

// Obtiene el registro de internado más reciente
const getLatestReg = (est: any) => {
    if (!est) return null;
    const regs = est.registrosInternado || est.registros_internado || [];
    if (regs.length === 0) return null;
    const sorted = [...regs].sort((a, b) => {
        const yearA = parseInt(a.gestion?.nombre_gestion || '0');
        const yearB = parseInt(b.gestion?.nombre_gestion || '0');
        if (yearA !== yearB) return yearB - yearA;
        return (b.id || 0) - (a.id || 0);
    });
    return sorted[0];
};

// Obtiene las gestiones/registros ordenados cronológicamente descendente
const sortedRegistrations = (est: any) => {
    if (!est) return [];
    const regs = est.registrosInternado || est.registros_internado || [];
    return [...regs].sort((a, b) => {
        const yearA = parseInt(a.gestion?.nombre_gestion || '0');
        const yearB = parseInt(b.gestion?.nombre_gestion || '0');
        return yearB - yearA;
    });
};
</script>

<template>
    <!-- MODAL FICHA DETALLADA / VISTA COMPLETA -->
    <div v-if="show && estudiante" class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Capa de desenfoque de fondo -->
            <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="emit('close')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Contenedor del contenido del modal -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border">
                <div class="bg-white p-6 relative">
                    <div class="flex items-start justify-between border-b pb-4 mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Ficha Técnica e Historial del Estudiante</h3>
                            <p class="text-xs text-gray-400">
                                CI: {{ estudiante.persona?.ci }} &bull; Fecha Registro: {{ new Date(estudiante.created_at).toLocaleDateString() }}
                            </p>
                        </div>
                        <button @click="emit('close')" class="text-gray-400 hover:text-gray-700">✕</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Columna de Fotografía y Estado -->
                        <div class="md:col-span-1 flex flex-col items-center border-r md:pr-6 border-gray-100">
                            <div class="w-32 h-44 bg-gray-100 rounded-xl overflow-hidden border border-gray-200 flex items-center justify-center shadow-md">
                                <img v-if="estudiante.ruta_foto" :src="estudiante.ruta_foto" class="w-full h-full object-cover">
                                <span v-else class="text-5xl">👤</span>
                            </div>
                            <span class="mt-4 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-teal-50 text-teal-700 border border-teal-200">
                                {{ estudiante.estado_global || 'Activo' }}
                            </span>
                        </div>

                        <!-- Detalles de la Ficha -->
                        <div class="md:col-span-2 space-y-4 text-sm">
                            <div>
                                <h4 class="font-bold text-gray-400 text-xs uppercase tracking-wider">Estudiante</h4>
                                <p class="text-base font-bold text-slate-800">
                                    {{ estudiante.persona?.nombre }} {{ estudiante.persona?.ap_paterno }} {{ estudiante.persona?.ap_materno }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Género: {{ estudiante.persona?.sexo === 'M' ? 'Masculino' : 'Femenino' }} &bull; Celular: {{ estudiante.persona?.celular || 'S/N' }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <h4 class="font-bold text-gray-400 text-xs uppercase tracking-wider">Ubicación Habitación</h4>
                                    <p class="font-semibold text-gray-800">{{ getLatestReg(estudiante)?.pabellon || 'S/N' }}</p>
                                    <p class="text-xs text-gray-500">Cama: {{ getLatestReg(estudiante)?.cama || 'S/N' }}</p>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-400 text-xs uppercase tracking-wider">Procedencia</h4>
                                    <p class="font-semibold text-gray-800">{{ estudiante.comunidad?.nombre || 'S/N' }}</p>
                                    <p class="text-xs text-gray-500">Provincia: {{ estudiante.comunidad?.provincia?.nombre || 'S/N' }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <h4 class="font-bold text-gray-400 text-xs uppercase tracking-wider">Curso Regular Activo</h4>
                                    <p class="font-semibold text-gray-800">{{ getLatestReg(estudiante)?.curso?.nombre || 'Ninguno' }}</p>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-400 text-xs uppercase tracking-wider">Carrera BTH</h4>
                                    <p class="font-semibold text-gray-800 text-xs truncate">
                                        {{ getLatestReg(estudiante)?.curso_bth?.carrera_tecnica?.nombre || 'Ninguna Especialidad' }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="estudiante.tutor?.persona" class="p-3 bg-amber-50/50 rounded-xl border border-amber-100/50">
                                <h4 class="font-bold text-amber-800 text-xs uppercase tracking-wider">Apoderado / Tutor</h4>
                                <p class="font-bold text-gray-800 text-xs mt-1">
                                    {{ estudiante.tutor.persona.nombre }} {{ estudiante.tutor.persona.ap_paterno }} (Cel: {{ estudiante.tutor.persona.celular || 'S/N' }})
                                </p>
                            </div>

                            <!-- Motivo de Retiro / Anulación -->
                            <div v-if="estudiante.estado_global === 'Retirado' && estudiante.motivo_retiro" class="p-3 bg-rose-50 rounded-xl border border-rose-100 text-xs">
                                <h4 class="font-bold text-rose-800 text-[10px] uppercase tracking-wider">Motivo de Retiro</h4>
                                <p class="text-rose-900 font-bold mt-1">{{ estudiante.motivo_retiro }}</p>
                            </div>

                            <div v-if="estudiante.estado_global === 'Anulado' && estudiante.motivo_anulacion" class="p-3 bg-slate-100 rounded-xl border border-slate-200 text-xs">
                                <h4 class="font-bold text-slate-800 text-[10px] uppercase tracking-wider">Motivo de Anulación</h4>
                                <p class="text-slate-900 font-bold mt-1">{{ estudiante.motivo_anulacion }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Trayectoria y registros de gestión -->
                    <div class="mt-6 border-t pt-4">
                        <h4 class="font-bold text-gray-800 text-xs uppercase tracking-wider mb-3">📜 Trayectoria y Registro de Gestión</h4>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            <div v-for="reg in sortedRegistrations(estudiante)" :key="reg.id" class="p-2.5 bg-gray-50 border rounded-xl text-xs flex flex-col gap-1">
                                <div class="flex justify-between items-center w-full">
                                    <div>
                                        <span class="font-bold text-teal-700 bg-teal-50 border px-1.5 py-0.5 rounded mr-2">
                                            Gestión {{ reg.gestion?.nombre_gestion }}
                                        </span>
                                        <strong class="text-gray-800">{{ reg.curso?.nombre || 'S/N' }}</strong>
                                        <span class="text-gray-400 mx-1">&bull;</span>
                                        <span class="text-gray-500">BTH: {{ reg.curso_bth?.carrera_tecnica?.nombre || 'Ninguno' }}</span>
                                    </div>
                                    <span class="font-black uppercase tracking-wider text-[10px] border px-2 py-0.5 rounded-full"
                                        :class="{
                                            'text-green-800 bg-green-100 border-green-200': reg.estado_anual === 'Aprobado',
                                            'text-red-800 bg-red-100 border-red-200': reg.estado_anual === 'Reprobado',
                                            'text-rose-800 bg-rose-100 border-rose-200': reg.estado_anual === 'Retirado',
                                            'text-indigo-800 bg-indigo-100 border-indigo-200': reg.estado_anual === 'Aprobado y Retirado',
                                            'text-orange-800 bg-orange-100 border-orange-200': reg.estado_anual === 'Reprobado y Retirado',
                                            'text-yellow-800 bg-yellow-100 border-yellow-200': reg.estado_anual === 'Cursando'
                                        }">
                                        {{ reg.estado_anual }}
                                    </span>
                                </div>
                                <div v-if="reg.motivo_retiro" class="text-[10px] text-rose-700 bg-rose-50/50 border border-rose-100 p-1.5 rounded-lg mt-1 font-medium">
                                    <strong>Motivo de Retiro:</strong> {{ reg.motivo_retiro }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones del modal de ficha -->
                <div class="bg-gray-50 px-6 py-4 flex justify-between rounded-b-2xl border-t">
                    <button @click="emit('edit', estudiante)" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-5 rounded-xl text-xs shadow-md transition">
                        Editar Ficha
                    </button>
                    <button @click="emit('close')" class="bg-white hover:bg-gray-50 text-slate-700 border font-bold py-2 px-5 rounded-xl text-xs shadow-sm transition">
                        Cerrar Ficha
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
