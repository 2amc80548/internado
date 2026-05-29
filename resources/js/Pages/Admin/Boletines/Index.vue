<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    cursos: Array,
    registros: Array
});

const selectedCurso = ref(props.cursos && props.cursos.length > 0 ? (props.cursos[0] as any).id : null);

const filteredRegistros = computed(() => {
    if (!props.registros) return [];
    return props.registros.filter((r: any) => r.curso_id === selectedCurso.value);
});

// Asumimos que todos los estudiantes del filtro comparten la gestión actual
const maxPeriodos = computed(() => {
    if (filteredRegistros.value.length === 0) return 3; // Default
    return filteredRegistros.value[0].gestion?.cantidad_periodos || 3;
});

const tipoPeriodo = computed(() => {
    if (filteredRegistros.value.length === 0) return 'Trimestre'; // Default
    return filteredRegistros.value[0].gestion?.tipo_periodo_academico || 'Trimestre';
});

const getBoletin = (registro: any, periodo: number) => {
    return registro.boletines?.find((b: any) => b.numero_periodo === periodo);
};

const showModal = ref(false);
const activeBoletin = ref<any>(null);

const viewBoletin = (boletin: any) => {
    activeBoletin.value = boletin;
    showModal.value = true;
};
</script>

<template>
    <Head title="Control de Boletines" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                Control Académico y Boletines
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Filtros -->
                    <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Filtrar por Grado</h3>
                            <p class="text-sm text-gray-500">Seleccione el curso para ver las calificaciones de sus estudiantes.</p>
                        </div>
                        <div class="w-full sm:w-64">
                            <select v-model="selectedCurso" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-lg">
                                <option v-for="curso in (cursos as any[])" :key="curso.id" :value="curso.id">{{ curso.nombre }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabla de Boletines -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estudiante</th>
                                    <th v-for="periodo in maxPeriodos" :key="periodo" scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        {{ tipoPeriodo }} {{ periodo }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="registro in filteredRegistros" :key="registro.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-700 font-bold">
                                                {{ registro.estudiante?.persona?.nombre?.charAt(0) || 'E' }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-gray-900">{{ registro.estudiante?.persona?.nombre }} {{ registro.estudiante?.persona?.ap_paterno }}</div>
                                                <div class="text-xs text-gray-500">CI: {{ registro.estudiante?.persona?.ci }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td v-for="periodo in maxPeriodos" :key="periodo" class="px-6 py-4 whitespace-nowrap text-center">
                                        <div v-if="getBoletin(registro, periodo)">
                                            <button @click="viewBoletin(getBoletin(registro, periodo))" class="inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-teal-700 bg-teal-100 hover:bg-teal-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-colors">
                                                Ver Archivo
                                            </button>
                                        </div>
                                        <div v-else>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Falta
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredRegistros.length === 0">
                                    <td :colspan="maxPeriodos + 1" class="px-6 py-8 text-center text-gray-500">
                                        No hay estudiantes registrados en este curso en la gestión activa.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Visor Modal de Boletín -->
                <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="showModal = false" aria-hidden="true"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-xl leading-6 font-bold text-gray-900 mb-4 text-center">Visor de Boletín Escolar</h3>
                                <div class="rounded-lg overflow-hidden border border-gray-200 bg-gray-100 flex items-center justify-center min-h-[400px]">
                                    <embed v-if="activeBoletin?.ruta_archivo.endsWith('.pdf')" :src="`/storage/${activeBoletin?.ruta_archivo}`" type="application/pdf" width="100%" height="500px" />
                                    <img v-else :src="`/storage/${activeBoletin?.ruta_archivo}`" class="max-w-full h-auto object-contain" />
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                                <button @click="showModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition">
                                    Cerrar Visor
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
