<script setup lang="ts">
import { ref, computed } from 'vue';

// Definición de las propiedades del componente
const props = defineProps<{
    show: boolean;
    gestiones: any[];
    cursos: any[];
    estudiantes: any[];
    activeGestion: any;
    activeGestionName: string;
    sourceGestiones: any[];
    immediatePreviousGestion: any;
    editActiveDatesMode: boolean;
    activeDatesForm: any;
    promotionForm: any;
    ofertarForm: any;
    selectedSourceGestionId: number | string;
}>();

// Definición de los eventos emitidos por el componente
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'update-source-gestion', val: number | string): void;
    (e: 'trigger-edit-dates'): void;
    (e: 'cancel-edit-dates'): void;
    (e: 'submit-active-dates'): void;
    (e: 'submit-promotion'): void;
    (e: 'submit-oferta', callback: () => void): void;
}>();

// Control local de la visibilidad del modal secundario para ofertar gestión
const showOfertarModal = ref(false);

// Filtro de curso en el listado del wizard
const filterCursoWizard = ref('');

// Cambia el estado y recalcula el curso sugerido para el estudiante
const handleStatusChange = (index: number) => {
    const row = props.promotionForm.promociones[index];
    const est = props.estudiantes.find(e => e.id === row.estudiante_id);
    const regs = est?.registrosInternado || est?.registros_internado || [];
    const regSource = regs.find((r: any) => String(r.gestion_id) === String(props.selectedSourceGestionId));
    
    if (row.estado_anual.includes('Retirado')) {
        row.curso_id = '';
        row.curso_bth_id = '';
    } else if (row.estado_anual === 'Reprobado') {
        row.curso_id = regSource?.curso_id || '';
    } else if (row.estado_anual === 'Aprobado') {
        const currentCursoId = regSource?.curso_id || '';
        let nextCursoId = currentCursoId;
        if (currentCursoId) {
            const curIdx = props.cursos.findIndex(c => String(c.id) === String(currentCursoId));
            if (curIdx !== -1 && curIdx + 1 < props.cursos.length) {
                nextCursoId = props.cursos[curIdx + 1].id;
            }
        }
        row.curso_id = nextCursoId;
    }
};

// Cierra el modal de ofertar gestión reseteando el formulario
const closeOfertarModal = () => {
    showOfertarModal.value = false;
};

// Maneja el envío del formulario de oferta
const handleOfertaSubmit = () => {
    emit('submit-oferta', () => {
        showOfertarModal.value = false;
    });
};
</script>

<template>
    <!-- MODAL DE ASISTENTE DE PROMOCION Y CIERRE DE GESTION -->
    <div v-if="show" class="fixed z-40 inset-0 overflow-y-auto animate-fade-in">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Capa de desenfoque de fondo -->
            <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="emit('close')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Contenedor del contenido del modal -->
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full border border-slate-100">
                
                <div class="bg-white p-6 max-h-[85vh] overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b pb-4">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800">Cierre de Gestión Académica y Promoción Escolar</h3>
                            <p class="text-xs text-gray-500">Este asistente creará una nueva gestión académica e inscribirá de forma masiva a los estudiantes cursando activos.</p>
                        </div>
                        <button @click="emit('close')" class="text-gray-400 hover:text-gray-700">✕</button>
                    </div>

                    <!-- Panel superior de selección de gestiones de origen y destino -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-slate-50 p-5 border border-slate-200 rounded-2xl">
                        <!-- Origen -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-black text-slate-500 uppercase">1. Seleccionar Gestión Escolar de Origen (Anterior)</label>
                            <div class="flex gap-2 mt-1.5">
                                <select :value="selectedSourceGestionId" @change="emit('update-source-gestion', ($event.target as HTMLSelectElement).value)" class="block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500 font-bold text-teal-700 py-2">
                                    <option value="" disabled>Seleccione la gestión anterior...</option>
                                    <option v-for="g in sourceGestiones" :key="g.id" :value="g.id" :disabled="g.id !== immediatePreviousGestion?.id">
                                        Gestión {{ g.nombre_gestion }} ({{ g.fecha_inicio }} al {{ g.fecha_fin }}) {{ g.id !== immediatePreviousGestion?.id ? ' (Inhabilitada)' : ' (Origen Recomendado)' }}
                                    </option>
                                </select>
                                <button @click="showOfertarModal = true" type="button" class="bg-teal-600 hover:bg-teal-700 text-white font-bold px-4 py-2 rounded-lg text-xs transition duration-200 shrink-0 shadow-sm flex items-center gap-1.5 animate-pulse">
                                    <span>📢</span> Ofertar Gestión
                                </button>
                            </div>
                        </div>
                        
                        <!-- Destino -->
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase">2. Gestión Destino (Activa Actual)</label>
                            <div class="mt-2">
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="px-3 py-1.5 rounded-lg text-sm font-black bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wide">
                                            {{ activeGestionName }} (Activa)
                                        </span>
                                        <button v-if="activeGestion && !editActiveDatesMode" @click="emit('trigger-edit-dates')" type="button" class="text-xs font-black text-indigo-600 hover:text-indigo-800 transition flex items-center gap-1" title="Editar Fechas">
                                            ✏️ Editar Fechas
                                        </button>
                                    </div>
                                    
                                    <!-- Visualizar fechas actuales -->
                                    <div v-if="activeGestion && !editActiveDatesMode" class="text-xs text-gray-500 font-semibold mt-0.5">
                                        📅 {{ activeGestion.fecha_inicio }} al {{ activeGestion.fecha_fin }}
                                    </div>

                                    <!-- Formulario inline para modificar fechas -->
                                    <div v-if="editActiveDatesMode" class="bg-indigo-50/50 p-2.5 rounded-xl border border-indigo-100 space-y-2 mt-1 w-full max-w-[280px]">
                                        <div class="space-y-1">
                                            <label class="block text-[9px] font-bold text-gray-500 uppercase">Fecha Inicio</label>
                                            <input v-model="activeDatesForm.fecha_inicio" type="date" required class="border-gray-300 rounded-lg text-xs py-0.5 px-2 w-full">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-[9px] font-bold text-gray-500 uppercase">Fecha Fin</label>
                                            <input v-model="activeDatesForm.fecha_fin" type="date" required class="border-gray-300 rounded-lg text-xs py-0.5 px-2 w-full">
                                        </div>
                                        <div class="flex gap-2 justify-end pt-1">
                                            <button @click="emit('cancel-edit-dates')" type="button" class="bg-white border rounded px-2 py-0.5 text-[9px] font-bold text-gray-600 hover:bg-gray-50">Cancelar</button>
                                            <button @click="emit('submit-active-dates')" type="button" :disabled="activeDatesForm.processing" class="bg-indigo-600 text-white rounded px-2 py-0.5 text-[9px] font-bold hover:bg-indigo-700">
                                                {{ activeDatesForm.processing ? 'Guardando...' : 'Guardar' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Planilla del Wizard -->
                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50 p-4 rounded-xl border border-slate-100">
                            <div>
                                <h4 class="font-black text-slate-700 text-xs uppercase tracking-wider block">Planilla de Promoción de Alumnos</h4>
                                <p class="text-[10px] text-gray-500 mt-0.5">Selecciona con checkbox los alumnos de la gestión anterior y promociónalos.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <label class="text-xs font-bold text-gray-500">Filtrar Curso:</label>
                                    <select v-model="filterCursoWizard" class="border-gray-300 rounded-lg text-xs shadow-sm focus:ring-teal-500 focus:border-teal-500 py-1 px-2 font-bold text-teal-700 bg-white">
                                        <option value="">Todos los cursos</option>
                                        <option v-for="c in cursos" :key="c.id" :value="c.nombre">{{ c.nombre }}</option>
                                    </select>
                                </div>
                                <span class="px-2.5 py-1 rounded-md text-xs font-black bg-indigo-100 text-indigo-800 border border-indigo-200">
                                    Alumnos Marcados: {{ promotionForm.promociones.filter((p: any) => p.selected && !p.alreadyPromoted).length }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="border rounded-2xl overflow-hidden max-h-60 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase w-12">Promover</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Estudiante</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Curso Anterior</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase">Estado Académico</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Curso Siguiente / Motivo</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100 text-sm text-slate-600">
                                    <tr v-for="(p, index) in promotionForm.promociones" :key="p.estudiante_id" v-show="!filterCursoWizard || p.curso_actual_nombre === filterCursoWizard" class="hover:bg-gray-50/50">
                                        <td class="px-4 py-2 text-center">
                                            <span v-if="p.isGraduated" class="text-indigo-600 font-black text-[10px] bg-indigo-50 border px-1.5 py-0.5 rounded">🎓 Egresado</span>
                                            <span v-else-if="p.isRetirado" class="text-rose-600 font-black text-[10px] bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded">❌ Retirado</span>
                                            <span v-else-if="p.alreadyPromoted" class="text-emerald-600 font-bold text-xs">✔️ Cursando</span>
                                            <input v-else type="checkbox" v-model="p.selected" class="rounded text-teal-600 focus:ring-teal-500 w-4 h-4 cursor-pointer">
                                        </td>
                                        <td class="px-4 py-2 font-semibold" :class="{'text-gray-400': p.alreadyPromoted, 'text-slate-800': !p.alreadyPromoted}">
                                            {{ p.nombre_completo }} <span class="text-gray-400 font-medium text-xs">({{ p.ci }})</span>
                                        </td>
                                        <td class="px-4 py-2 font-bold text-teal-700" :class="{'opacity-50': p.alreadyPromoted}">{{ p.curso_actual_nombre }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <span v-if="p.isGraduated" class="text-xs font-black text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded">Aprobado</span>
                                            <span v-else-if="p.isRetirado" class="text-xs font-black text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded">Retirado</span>
                                            <span v-else-if="p.alreadyPromoted" class="text-xs font-bold text-indigo-700 bg-indigo-50 border px-2 py-0.5 rounded">{{ p.estado_anual }}</span>
                                            <select v-else v-model="p.estado_anual" @change="handleStatusChange(index)" class="border-gray-300 rounded-lg text-xs py-1 focus:ring-teal-500 focus:border-teal-500 bg-slate-50 font-bold">
                                                <option value="Aprobado">Aprobado</option>
                                                <option value="Reprobado">Reprobado</option>
                                                <option value="Retirado">Retirado</option>
                                                <option value="Aprobado y Retirado">Aprobado y Retirado</option>
                                                <option value="Reprobado y Retirado">Reprobado y Retirado</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2">
                                            <span v-if="p.isGraduated" class="text-[10px] font-black text-indigo-800 bg-indigo-100 px-2 py-0.5 rounded flex items-center gap-1 w-max">
                                                🎓 Egresa Bachiller
                                            </span>
                                            <span v-else-if="p.isRetirado" class="text-[10px] font-black text-rose-800 bg-rose-100 px-2 py-0.5 rounded block max-w-[200px] truncate" :title="p.motivo_retiro">
                                                Retirado: {{ p.motivo_retiro || 'Sin motivo' }}
                                            </span>
                                            <span v-else-if="p.alreadyPromoted" class="text-[10px] font-black text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded">Ya Promocionado</span>
                                            <template v-else>
                                                <div v-if="p.estado_anual.includes('Retirado')" class="space-y-1">
                                                    <input type="text" v-model="p.motivo_retiro" placeholder="Especificar Motivo de Retiro *" class="border-red-300 rounded-lg text-xs py-1 px-2 w-full bg-red-50 text-red-900 font-medium" required>
                                                </div>
                                                <span v-else-if="(p.curso_actual_nombre.includes('6to') || p.curso_actual_nombre.includes('Sexto')) && p.estado_anual === 'Aprobado'" class="text-xs px-2.5 py-1 rounded bg-indigo-100 text-indigo-800 border border-indigo-200 flex items-center gap-1 w-max font-black animate-pulse">
                                                    🎓 Egresa Bachiller
                                                </span>
                                                <select v-else v-model="p.curso_id" :disabled="p.estado_anual.includes('Retirado')" class="border-gray-300 rounded-lg text-xs py-1 focus:ring-teal-500 focus:border-teal-500 max-w-[200px] font-black bg-white">
                                                    <option value="">Ninguno</option>
                                                    <option v-for="c in cursos" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                                                </select>
                                            </template>
                                        </td>
                                    </tr>
                                    <tr v-if="promotionForm.promociones.length === 0">
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-400 font-bold text-xs">
                                            No hay estudiantes registrados en la gestión de origen seleccionada.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse rounded-b-3xl border-t border-slate-100">
                    <button @click="emit('submit-promotion')" :disabled="promotionForm.processing" type="button" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-gradient-to-r from-teal-600 to-teal-700 text-sm font-bold text-white hover:from-teal-700 hover:to-teal-800 sm:ml-3 sm:w-auto transition transform hover:scale-102">
                        {{ promotionForm.processing ? 'Procesando Promociones...' : 'Confirmar y Promocionar' }}
                    </button>
                    <button @click="emit('close')" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-200 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 sm:mt-0 sm:ml-3 sm:w-auto transition">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL SECUNDARIO: OFERTAR GESTION ACADEMICA -->
    <div v-if="showOfertarModal" class="fixed z-[60] inset-0 overflow-y-auto animate-fade-in">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/60" @click="closeOfertarModal"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100">
                <form @submit.prevent="handleOfertaSubmit">
                    <div class="bg-white p-6 space-y-4">
                        <h3 class="text-lg font-bold text-slate-800 border-b pb-3 flex items-center gap-2">
                            <span>📢</span> Ofertar Nueva Gestión Escolar
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Nombre de la Gestión (Año)</label>
                                <input v-model="ofertarForm.nombre_gestion" type="text" required placeholder="Ej. 2027" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500 font-bold text-teal-700">
                                <p v-if="ofertarForm.errors.nombre_gestion" class="text-rose-500 text-xs mt-1">{{ ofertarForm.errors.nombre_gestion }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Fecha de Inicio de Actividades</label>
                                <input v-model="ofertarForm.fecha_inicio" type="date" required class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Fecha de Finalización Prevista</label>
                                <input v-model="ofertarForm.fecha_fin" type="date" required class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Distribución de Periodos</label>
                                <select v-model="ofertarForm.cantidad_periodos" @change="ofertarForm.tipo_periodo_academico = ofertarForm.cantidad_periodos === 4 ? 'Bimestre' : (ofertarForm.cantidad_periodos === 3 ? 'Trimestre' : 'Semestre')" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                    <option :value="4">4 Bimestres</option>
                                    <option :value="3">3 Trimestres</option>
                                    <option :value="2">2 Semestres</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse border-t gap-2">
                        <button type="submit" :disabled="ofertarForm.processing" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-sm font-bold text-white transition sm:ml-3 sm:w-auto">
                            {{ ofertarForm.processing ? 'Registrando...' : 'Ofertar Gestión' }}
                        </button>
                        <button type="button" @click="closeOfertarModal" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto transition">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
