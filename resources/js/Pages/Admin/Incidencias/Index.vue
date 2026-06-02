<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
    estudiantes: any[];
    searchQuery: string;
    gestionActual: any;
}>();

const searchInput = ref(props.searchQuery);
const selectedEstudiante = ref<any>(null);

// Form and Modals
const showModal = ref(false);
const isEditing = ref(false);
const editingIncidenciaId = ref<number | null>(null);

const form = useForm({
    registro_internado_id: '' as string | number,
    fecha: new Date().toISOString().substring(0, 10),
    tipo_falta: 'Leve',
    descripcion: '',
    sancion: '',
    estado_sancion: 'Pendiente'
});

const doSearch = () => {
    selectedEstudiante.value = null;
    router.get(route('incidencias.index'), { search: searchInput.value }, {
        preserveState: true,
        onSuccess: () => {
            if (props.estudiantes.length > 0) {
                // Auto select first match if only 1 is found
                if (props.estudiantes.length === 1) {
                    selectStudent(props.estudiantes[0]);
                }
            }
        }
    });
};

const selectStudent = (student: any) => {
    selectedEstudiante.value = student;
    form.registro_internado_id = student.registros_internado?.[0]?.id || '';
};

const openCreateModal = () => {
    if (!selectedEstudiante.value) return;
    isEditing.value = false;
    editingIncidenciaId.value = null;
    form.reset('fecha', 'tipo_falta', 'descripcion', 'sancion', 'estado_sancion');
    form.registro_internado_id = selectedEstudiante.value.registros_internado?.[0]?.id || '';
    form.fecha = new Date().toISOString().substring(0, 10);
    showModal.value = true;
};

const openEditModal = (inc: any) => {
    isEditing.value = true;
    editingIncidenciaId.value = inc.id;
    form.registro_internado_id = inc.registro_internado_id;
    form.fecha = inc.fecha;
    form.tipo_falta = inc.tipo_falta;
    form.descripcion = inc.descripcion;
    form.sancion = inc.sancion || '';
    form.estado_sancion = inc.estado_sancion;
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value && editingIncidenciaId.value) {
        form.put(route('incidencias.update', editingIncidenciaId.value), {
            onSuccess: () => {
                showModal.value = false;
                // Refresh local selected student incidencias list
                refreshSelectedStudent();
                alert('Incidencia actualizada correctamente.');
            }
        });
    } else {
        form.post(route('incidencias.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset('descripcion', 'sancion');
                refreshSelectedStudent();
                alert('Incidencia registrada con éxito.');
            }
        });
    }
};

const deleteIncidencia = (id: number) => {
    if (confirm('¿Está seguro de eliminar permanentemente este registro de incidencia?')) {
        router.delete(route('incidencias.destroy', id), {
            onSuccess: () => {
                refreshSelectedStudent();
                alert('Incidencia eliminada.');
            }
        });
    }
};

const refreshSelectedStudent = () => {
    if (!selectedEstudiante.value) return;
    const estId = selectedEstudiante.value.id;
    // We can pull updated student from the page.props.estudiantes list
    const updated = props.estudiantes.find(e => e.id === estId);
    if (updated) {
        selectedEstudiante.value = updated;
    }
};

const activeReg = computed(() => {
    return selectedEstudiante.value?.registros_internado?.[0] || null;
});

const incidenciasList = computed(() => {
    return activeReg.value?.incidencias || [];
});
</script>

<template>
    <Head title="Control de Incidencias" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-2xl text-gray-800 leading-tight">
                Control de Incidencias Disciplinarias
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Warning if no active gestion -->
                <div v-if="!gestionActual" class="p-6 bg-amber-50 rounded-3xl border border-amber-200 text-amber-900 flex items-center gap-4">
                    <span class="text-3xl">⚠️</span>
                    <div>
                        <h4 class="font-black">No hay una gestión activa en curso</h4>
                        <p class="text-sm">Por favor oferta e inicia una nueva gestión escolar desde el módulo de Estudiantes para registrar incidencias disciplinarias.</p>
                    </div>
                </div>

                <!-- Buscador de Rendimiento -->
                <div v-else class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                    <div class="max-w-2xl mx-auto text-center space-y-4">
                        <h3 class="text-lg font-black text-slate-800">🔍 Buscar Estudiante para Registrar Falta</h3>
                        <p class="text-xs text-gray-500">Busca incrementalmente por Nombres, Apellidos o C.I. del estudiante para evitar sobrecargar el sistema.</p>
                        
                        <form @submit.prevent="doSearch" class="flex gap-2">
                            <input v-model="searchInput" 
                                   type="text" 
                                   placeholder="Ingresa Nombres, Apellidos o C.I. del estudiante..." 
                                   class="flex-1 rounded-2xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 font-medium py-3 px-4 text-sm" 
                                   required>
                            <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-extrabold px-6 rounded-2xl text-sm transition-all duration-200 flex items-center gap-2 shadow-md">
                                <span>Buscar</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div v-if="gestionActual" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    
                    <!-- Left column: Search Results List -->
                    <div class="lg:col-span-1 space-y-4">
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 min-h-[300px]">
                            <h4 class="font-bold text-slate-700 text-xs uppercase tracking-wider mb-4 border-b pb-2">Resultados de Búsqueda</h4>
                            
                            <div v-if="estudiantes.length === 0" class="py-12 text-center text-gray-400 space-y-3">
                                <span class="text-4xl block">🔍</span>
                                <p class="text-xs font-bold" v-if="searchQuery">No se encontraron estudiantes con "{{ searchQuery }}".</p>
                                <p class="text-xs font-bold" v-else>Escribe el nombre o C.I. arriba y presiona buscar.</p>
                            </div>
                            
                            <div v-else class="space-y-2">
                                <button v-for="est in estudiantes" 
                                        :key="est.id"
                                        @click="selectStudent(est)"
                                        :class="[selectedEstudiante?.id === est.id ? 'border-teal-500 bg-teal-50/50 ring-2 ring-teal-500/20' : 'border-gray-100 bg-gray-50 hover:bg-gray-100/70']"
                                        class="w-full border p-4 rounded-2xl text-left transition duration-200 flex items-center justify-between gap-3 group">
                                    <div class="overflow-hidden">
                                        <h5 class="font-bold text-slate-800 text-sm truncate uppercase group-hover:text-teal-600 transition">{{ est.persona?.nombre }} {{ est.persona?.ap_paterno }}</h5>
                                        <p class="text-[11px] text-gray-500 mt-0.5">CI: {{ est.persona?.ci }} &bull; Curso: {{ est.registros_internado?.[0]?.curso?.nombre || 'Ninguno' }}</p>
                                    </div>
                                    <span class="text-teal-500 opacity-0 group-hover:opacity-100 transition">👉</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Student details & Incidences timeline -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 min-h-[300px]">
                            
                            <!-- State: Empty detail placeholder -->
                            <div v-if="!selectedEstudiante" class="py-24 text-center text-gray-400 space-y-3">
                                <span class="text-5xl block">📜</span>
                                <h4 class="font-bold text-slate-700 text-sm">Ficha de Faltas Disciplinarias</h4>
                                <p class="text-xs max-w-sm mx-auto">Selecciona un estudiante del listado izquierdo para visualizar su historial disciplinario o registrar nuevas incidencias.</p>
                            </div>

                            <!-- State: Selected Student Details -->
                            <div v-else class="space-y-6">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b pb-4 gap-4">
                                    <div>
                                        <h3 class="text-xl font-black text-slate-800 uppercase">{{ selectedEstudiante.persona?.nombre }} {{ selectedEstudiante.persona?.ap_paterno }} {{ selectedEstudiante.persona?.ap_materno }}</h3>
                                        <p class="text-xs text-gray-500 mt-1">C.I.: <strong class="text-slate-800">{{ selectedEstudiante.persona?.ci }}</strong> &bull; Curso: <strong class="text-slate-800">{{ activeReg?.curso?.nombre || 'S/N' }}</strong></p>
                                    </div>
                                    <button @click="openCreateModal" class="bg-teal-600 hover:bg-teal-700 text-white font-extrabold py-2.5 px-4 rounded-xl text-xs shadow-md transition-all duration-200 flex items-center gap-1.5 shrink-0">
                                        ➕ Registrar Falta
                                    </button>
                                </div>

                                <!-- Historial / Timeline -->
                                <div>
                                    <h4 class="font-bold text-slate-700 text-xs uppercase tracking-wider mb-4">Historial de la Gestión {{ gestionActual.nombre_gestion }}</h4>
                                    
                                    <div v-if="incidenciasList.length === 0" class="py-12 bg-gray-50 border-2 border-dashed border-gray-200 rounded-3xl text-center text-gray-500 space-y-2">
                                        <span class="text-3xl block">😇</span>
                                        <h5 class="font-bold text-sm">Sin incidencias registradas</h5>
                                        <p class="text-xs text-gray-400">El estudiante no cuenta con amonestaciones o faltas disciplinarias en este ciclo.</p>
                                    </div>

                                    <div v-else class="space-y-4">
                                        <div v-for="inc in incidenciasList" :key="inc.id" class="p-5 border rounded-2xl bg-white shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:shadow-md transition">
                                            <div class="space-y-2 overflow-hidden flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-xs font-black text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-100">{{ new Date(inc.fecha).toLocaleDateString() }}</span>
                                                    
                                                    <span :class="{
                                                        'bg-yellow-100 text-yellow-800 border-yellow-200': inc.tipo_falta === 'Leve',
                                                        'bg-orange-100 text-orange-800 border-orange-200': inc.tipo_falta === 'Grave',
                                                        'bg-red-100 text-red-800 border-red-200': inc.tipo_falta === 'Muy Grave'
                                                    }" class="px-2 py-0.5 border text-[10px] font-black uppercase rounded-full">
                                                        Falta {{ inc.tipo_falta }}
                                                    </span>

                                                    <span :class="{
                                                        'bg-amber-100 text-amber-800 border-amber-200': inc.estado_sancion === 'Pendiente',
                                                        'bg-green-100 text-green-800 border-green-200': inc.estado_sancion === 'Cumplida',
                                                        'bg-slate-100 text-slate-600 border-slate-200': inc.estado_sancion === 'Cancelada'
                                                    }" class="px-2.5 py-0.5 border text-[10px] font-black uppercase rounded-full">
                                                        Sanción: {{ inc.estado_sancion }}
                                                    </span>
                                                </div>
                                                <p class="text-sm font-bold text-slate-800">{{ inc.descripcion }}</p>
                                                <p v-if="inc.sancion" class="text-xs text-gray-500 font-medium bg-slate-50 p-2 rounded-lg border border-slate-100"><strong>Sanción Aplicada:</strong> {{ inc.sancion }}</p>
                                            </div>
                                            
                                            <!-- Actions -->
                                            <div class="flex gap-2 md:self-center shrink-0">
                                                <button @click="openEditModal(inc)" class="p-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-bold transition">
                                                    ✏️ Editar
                                                </button>
                                                <button @click="deleteIncidencia(inc.id)" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-bold transition">
                                                    🗑️ Borrar
                                                </button>
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

        <!-- MODAL: REGISTRAR / EDITAR INCIDENCIA -->
        <div v-if="showModal && selectedEstudiante" class="fixed z-50 inset-0 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="showModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <form @submit.prevent="submitForm">
                        <div class="bg-white px-6 pt-6 pb-4 space-y-4">
                            <h3 class="text-lg font-bold text-slate-800 border-b pb-2">
                                {{ isEditing ? '✏️ Editar Incidencia Disciplinaria' : '➕ Registrar Incidencia Disciplinaria' }}
                            </h3>
                            <p class="text-xs text-gray-500 font-semibold">
                                Registrando falta para: {{ selectedEstudiante.persona?.nombre }} {{ selectedEstudiante.persona?.ap_paterno }}
                            </p>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Fecha</label>
                                    <input v-model="form.fecha" type="date" required class="w-full border-gray-300 rounded-lg text-xs shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Gravedad de Falta</label>
                                    <select v-model="form.tipo_falta" required class="w-full border-gray-300 rounded-lg text-xs shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                        <option value="Leve">Leve</option>
                                        <option value="Grave">Grave</option>
                                        <option value="Muy Grave">Muy Grave</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Descripción de la Falta</label>
                                <textarea v-model="form.descripcion" required rows="3" placeholder="Describe detalladamente los hechos ocurridos..." class="w-full border-gray-300 rounded-lg text-xs shadow-sm focus:ring-teal-500 focus:border-teal-500"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Sanción Aplicada (Opcional)</label>
                                <textarea v-model="form.sancion" rows="2" placeholder="ej. Amonestación verbal, Limpieza de pabellón..." class="w-full border-gray-300 rounded-lg text-xs shadow-sm focus:ring-teal-500 focus:border-teal-500"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Estado de Sanción</label>
                                <select v-model="form.estado_sancion" required class="w-full border-gray-300 rounded-lg text-xs shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                    <option value="Pendiente">Pendiente (No cumplida)</option>
                                    <option value="Cumplida">Cumplida (Sancionado)</option>
                                    <option value="Cancelada">Cancelada (Disculpado/Anulado)</option>
                                </select>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse border-t gap-2 rounded-b-2xl">
                            <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2 bg-teal-600 hover:bg-teal-700 text-xs font-bold text-white transition sm:ml-3 sm:w-auto">
                                {{ form.processing ? 'Guardando...' : 'Guardar Incidencia' }}
                            </button>
                            <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2 bg-white text-xs font-bold text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto transition">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
