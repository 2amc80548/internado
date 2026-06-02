<script setup lang="ts">
import { ref, computed } from 'vue';

// Definición de las propiedades del componente
const props = defineProps<{
    showGenerarModal: boolean;
    mesesDisponibles: string[];
    formGenerar: any;
    estudiantesActivos: any[];
}>();

// Definición de los eventos emitidos por el componente
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
}>();

// Estado local de búsqueda dentro del listado de estudiantes en el modal
const searchStudentInModal = ref('');

// Lista computada de estudiantes filtrados por el término de búsqueda local
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

// Propiedad computada de doble vía para seleccionar/deseleccionar todos los estudiantes mostrados en el modal
const seleccionarTodos = computed({
    get() {
        const list = filteredEstudiantesActivos.value;
        return list.length > 0 && list.every(e => props.formGenerar.registro_internado_ids.includes(e.id));
    },
    set(val) {
        const listIds = filteredEstudiantesActivos.value.map(e => e.id);
        if (val) {
            listIds.forEach(id => {
                if (!props.formGenerar.registro_internado_ids.includes(id)) {
                    props.formGenerar.registro_internado_ids.push(id);
                }
            });
        } else {
            props.formGenerar.registro_internado_ids = props.formGenerar.registro_internado_ids.filter((id: number) => !listIds.includes(id));
        }
    }
});
</script>

<template>
    <!-- Modal de Generación Masiva -->
    <div v-if="showGenerarModal" class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Capa de desenfoque de fondo -->
            <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="emit('close')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Contenedor del contenido del modal -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form @submit.prevent="emit('submit')">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">Generación Masiva de Deudas</h3>
                        <p class="text-sm text-gray-500 mb-4">Seleccione a los estudiantes activos a los que desea generar la deuda.</p>
                        
                        <!-- Selector de Tipo de Deuda -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Deuda</label>
                            <select v-model="formGenerar.concepto_tipo" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                                <option value="mes">Mensualidad Regular (Mes)</option>
                                <option value="personalizado">Concepto Personalizado (Otro)</option>
                            </select>
                        </div>

                        <!-- Campos Concepto y Monto -->
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

                        <!-- Buscador Interno de Estudiantes en el Modal -->
                        <div class="mb-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Asignar a Estudiantes Específicos:</label>
                            <input type="text" v-model="searchStudentInModal" placeholder="🔍 Buscar estudiante por Nombre o C.I..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-xs py-1.5 px-3 bg-slate-50 mb-3">
                        </div>

                        <!-- Listado de Estudiantes con Checkbox para Selección Específica -->
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
                        
                        <!-- Alertas de Errores de Validación -->
                        <p class="text-xs text-red-600 mt-2 font-medium" v-if="formGenerar.errors.mes">{{ formGenerar.errors.mes }}</p>
                        <p class="text-xs text-red-600 mt-2 font-medium" v-if="formGenerar.errors.registro_internado_ids">{{ formGenerar.errors.registro_internado_ids }}</p>
                    </div>
                    
                    <!-- Acciones del Formulario del Modal -->
                    <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse rounded-b-2xl">
                        <button type="submit" :disabled="formGenerar.processing" class="w-full inline-flex justify-center rounded-lg shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ formGenerar.processing ? 'Generando...' : 'Generar' }}
                        </button>
                        <button @click="emit('close')" type="button" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
