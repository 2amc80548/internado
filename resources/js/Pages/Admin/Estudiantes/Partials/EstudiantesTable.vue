<script setup lang="ts">
import { ref } from 'vue';

// Definición de las propiedades del componente
const props = defineProps<{
    estudiantes: any[];
}>();

// Definición de los eventos emitidos por el componente
const emit = defineEmits<{
    (e: 'view', estudiante: any): void;
    (e: 'notes', estudiante: any): void;
    (e: 'edit', estudiante: any): void;
    (e: 'delete', estudiante: any): void;
    (e: 'load-all'): void;
}>();

// Obtiene el registro de internado más reciente del estudiante
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
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
        <!-- Estado vacío: carga optimizada -->
        <div v-if="estudiantes.length === 0" class="py-20 px-8 text-center max-w-lg mx-auto">
            <div class="h-16 w-16 rounded-full bg-teal-50 text-teal-600 flex items-center justify-center mx-auto mb-5 text-3xl shadow-sm">
                🔍
            </div>
            <h3 class="text-lg font-black text-gray-800 mb-1.5">Listado de Estudiantes</h3>
            <p class="text-sm text-gray-500 leading-relaxed mb-6">
                Para garantizar la máxima velocidad de carga, la lista no se carga por defecto. Escribe un término en el buscador y presiona Enter, selecciona un filtro o activa la carga completa para visualizar las fichas.
            </p>
            <button @click="emit('load-all')" class="inline-flex items-center justify-center bg-teal-600 hover:bg-teal-700 text-white font-black py-2.5 px-6 rounded-xl shadow-md transition text-xs">
                Cargar Todo el Listado
            </button>
        </div>

        <!-- Tabla de estudiantes -->
        <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estudiante</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ubicación y Cama</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tutor Apoderado</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Curso / Especialidad</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="est in estudiantes" :key="est.id" class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img v-if="est.ruta_foto" :src="est.ruta_foto" class="h-10 w-10 rounded-full object-cover">
                                    <div v-else class="h-10 w-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-800 font-bold">
                                        {{ est.persona?.nombre?.charAt(0) }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-bold text-gray-900">
                                        {{ est.persona?.nombre }} {{ est.persona?.ap_paterno }} {{ est.persona?.ap_materno || '' }}
                                    </div>
                                    <div class="text-xs text-gray-500">CI: {{ est.persona?.ci }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">{{ getLatestReg(est)?.pabellon || 'Sin Pabellón' }}</div>
                            <div class="text-xs text-gray-500">Cama: {{ getLatestReg(est)?.cama || 'S/N' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <template v-if="est.tutor?.persona">
                                <div class="text-xs text-gray-900 font-semibold">
                                    {{ est.tutor.persona.nombre }} {{ est.tutor.persona.ap_paterno }}
                                </div>
                                <div class="text-[10px] text-gray-500 mt-0.5">Cel: {{ est.tutor.persona.celular || 'S/N' }}</div>
                            </template>
                            <span v-else class="text-xs text-gray-400">Sin Tutor</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-xs text-gray-800 font-bold">Curso: {{ getLatestReg(est)?.curso?.nombre || 'Ninguno' }}</div>
                            <div class="text-[10px] text-indigo-600 font-semibold mt-1 max-w-[200px] truncate" :title="getLatestReg(est)?.curso_bth?.carrera_tecnica?.nombre">
                                BTH: {{ getLatestReg(est)?.curso_bth?.carrera_tecnica?.nombre || 'Ninguno' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="{
                                'bg-green-100 text-green-800 border-green-200': est.estado_global === 'Activo',
                                'bg-gray-100 text-gray-800 border-gray-200': est.estado_global === 'Retirado',
                                'bg-blue-100 text-blue-800 border-blue-200': est.estado_global === 'Bachiller',
                                'bg-indigo-100 text-indigo-800 border-indigo-200': est.estado_global === 'Graduado BTH',
                                'bg-red-100 text-red-800 border-red-200': est.estado_global === 'Anulado'
                            }" class="px-2.5 py-0.5 inline-flex text-[10px] font-black rounded-full border">
                                {{ est.estado_global || 'Activo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex justify-end items-center gap-2">
                            <!-- Ficha Detallada -->
                            <button @click="emit('view', est)" class="text-amber-600 hover:text-amber-900 bg-amber-50 p-2 rounded-lg" title="Ficha Completa">
                                🔍
                            </button>
                            <!-- Cargar Boletines -->
                            <button @click="emit('notes', est)" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg" title="Boletines (Notas)">
                                📚
                            </button>
                            <!-- Editar Ficha -->
                            <button @click="emit('edit', est)" class="text-teal-600 hover:text-teal-900 bg-teal-50 p-2 rounded-lg" title="Editar Ficha">
                                ✏️
                            </button>
                            <!-- Eliminar Ficha -->
                            <button @click="emit('delete', est)" class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg font-bold text-xs" title="Eliminar Estudiante Permanentemente">
                                🗑️ Eliminar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
