<script setup lang="ts">
import { ref, computed, watch } from 'vue';

// Definición de las propiedades del componente
const props = defineProps<{
    show: boolean;
    estudiante: any;
    subiendoBoletinAdmin: boolean;
    subiendoBoletinPeriodo: number | null;
}>();

// Definición de los eventos emitidos por el componente
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'upload-boletin', payload: { file: File; period: number; registroId: number }): void;
    (e: 'delete-boletin', boletinId: number): void;
}>();

// ID de gestión seleccionada de forma local
const selectedGestionId = ref<number | string>('');

// Registraciones disponibles para boletines de notas (Aprobados o Cursando)
const availableRegistrationsForNotes = computed(() => {
    if (!props.estudiante) return [];
    const regs = props.estudiante.registrosInternado || props.estudiante.registros_internado || [];
    
    const sorted = [...regs].sort((a: any, b: any) => {
        const yearA = parseInt(a.gestion?.nombre_gestion || '0');
        const yearB = parseInt(b.gestion?.nombre_gestion || '0');
        return yearB - yearA;
    });

    const filtered = sorted.filter((reg: any) => reg.estado_anual === 'Aprobado' || reg.estado_anual === 'Cursando');
    const seen = new Set();
    return filtered.filter((reg: any) => {
        if (seen.has(reg.gestion_id)) {
            return false;
        }
        seen.add(reg.gestion_id);
        return true;
    });
});

// Observador para inicializar y sincronizar la gestión seleccionada al cambiar de estudiante
watch(() => [props.estudiante, availableRegistrationsForNotes.value], () => {
    if (availableRegistrationsForNotes.value.length > 0) {
        const exists = availableRegistrationsForNotes.value.some(r => String(r.gestion_id) === String(selectedGestionId.value));
        if (!exists) {
            selectedGestionId.value = availableRegistrationsForNotes.value[0].gestion_id;
        }
    } else {
        selectedGestionId.value = '';
    }
}, { immediate: true });

// Registro específico de internado correspondiente a la gestión seleccionada
const selectedRegForNotes = computed(() => {
    if (!props.estudiante) return null;
    const regs = props.estudiante.registrosInternado || props.estudiante.registros_internado || [];
    if (selectedGestionId.value === '') {
        return regs[0] || null;
    }
    return regs.find((r: any) => String(r.gestion_id) === String(selectedGestionId.value)) || regs[0] || null;
});

// Manejador local de subida de boletines para validar archivo y emitir evento
const handleUpload = (e: Event, period: number) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0] && selectedRegForNotes.value) {
        const file = target.files[0];
        
        if (file.size > 5 * 1024 * 1024) {
            alert('El archivo no debe superar los 5MB');
            target.value = '';
            return;
        }
        
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            alert('Solo se permiten archivos PDF, JPG o PNG');
            target.value = '';
            return;
        }

        emit('upload-boletin', {
            file,
            period,
            registroId: selectedRegForNotes.value.id
        });
        target.value = '';
    }
};
</script>

<template>
    <!-- MODAL DE GESTIÓN DE BOLETINES (NOTAS) -->
    <div v-if="show && estudiante" class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Capa de desenfoque de fondo -->
            <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="emit('close')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Contenedor del contenido del modal -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border">
                <div class="bg-white p-6 relative">
                    <div class="flex items-center justify-between border-b pb-4 mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Boletines Escolares</h3>
                            <p class="text-xs text-gray-500">
                                Estudiante: {{ estudiante.persona?.nombre }} {{ estudiante.persona?.ap_paterno }}
                            </p>
                        </div>
                        <button @click="emit('close')" class="text-gray-400 hover:text-gray-700">✕</button>
                    </div>

                    <!-- Mensaje si no tiene inscripciones válidas -->
                    <div v-if="availableRegistrationsForNotes.length === 0" class="py-8 text-center text-gray-400 font-bold text-sm">
                        El estudiante no cuenta con inscripciones activas o cursadas registradas.
                    </div>
                    
                    <div v-else class="space-y-6">
                        <!-- Selector de Gestión Académica -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-gray-500 uppercase">Seleccionar Gestión Escolar:</label>
                            <select v-model="selectedGestionId" class="border-gray-300 rounded-lg text-xs shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <option v-for="reg in availableRegistrationsForNotes" :key="reg.id" :value="reg.gestion_id">
                                    Gestión {{ reg.gestion?.nombre_gestion }}
                                </option>
                            </select>
                        </div>

                        <!-- Detalle de periodos de la gestión seleccionada -->
                        <div v-if="selectedRegForNotes" class="bg-slate-50 p-4 border border-slate-100 rounded-xl space-y-4">
                            <div class="flex justify-between items-center text-xs text-gray-600 font-bold uppercase tracking-wider">
                                <span>Detalle Periodos Académicos</span>
                                <span class="text-teal-700">Dividido en: {{ selectedRegForNotes.gestion?.cantidad_periodos || 3 }} {{ selectedRegForNotes.gestion?.tipo_periodo_academico }}s</span>
                            </div>

                            <div class="grid grid-cols-1 gap-4 mt-2">
                                <div v-for="period in (selectedRegForNotes.gestion?.cantidad_periodos || 3)" :key="period" class="bg-white p-3 rounded-xl border border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-3">
                                    <span class="text-sm font-bold text-gray-700">
                                        {{ selectedRegForNotes.gestion?.tipo_periodo_academico }} {{ period }}
                                    </span>
                                    
                                    <div class="flex items-center gap-2">
                                        <!-- Si ya tiene boletín cargado -->
                                        <template v-if="selectedRegForNotes.boletines?.find((b: any) => b.numero_periodo === period)">
                                            <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 text-[10px] font-black uppercase">Boletín Subido</span>
                                            <a :href="`/storage/${selectedRegForNotes.boletines.find((b: any) => b.numero_periodo === period).ruta_archivo}`" target="_blank" class="text-xs underline hover:text-indigo-800 font-bold">Ver notas</a>
                                            <button @click="emit('delete-boletin', selectedRegForNotes.boletines.find((b: any) => b.numero_periodo === period).id)" class="text-red-500 hover:text-red-700 text-xs font-bold bg-red-50 p-1.5 rounded-lg">
                                                ✕ Eliminar
                                            </button>
                                        </template>
                                        
                                        <!-- Si no tiene boletín cargado -->
                                        <template v-else>
                                            <label class="cursor-pointer bg-teal-50 hover:bg-teal-100 text-teal-700 font-black py-1 px-3.5 rounded-lg text-xs transition border border-teal-100 inline-block text-center shadow-xs">
                                                <span>📤 Cargar Notas (PDF/JPG)</span>
                                                <input type="file" class="hidden" accept=".pdf,image/*" @change="handleUpload($event, period)">
                                            </label>
                                            <span v-if="subiendoBoletinAdmin && subiendoBoletinPeriodo === period" class="text-xs text-teal-600 font-bold animate-pulse">Subiendo...</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-4 flex justify-end rounded-b-2xl border-t">
                    <button @click="emit('close')" class="bg-white hover:bg-gray-50 text-slate-700 border font-bold py-2 px-5 rounded-xl text-xs shadow-sm transition">
                        Cerrar Boletines
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
