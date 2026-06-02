<script setup lang="ts">
// Definición de las propiedades del componente
defineProps<{
    showViewAnuladoModal: boolean;
    mensualidadVerAnulada: any;
}>();

// Definición de los eventos emitidos por el componente
const emit = defineEmits<{
    (e: 'close'): void;
}>();
</script>

<template>
    <!-- Modal Ver Motivo Anulacion -->
    <div v-if="showViewAnuladoModal" class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Capa de desenfoque de fondo -->
            <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="emit('close')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Contenedor del contenido del modal -->
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-slate-100 text-slate-700 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-black text-slate-800 leading-tight">Auditoría de Anulación</h3>
                    </div>
                    
                    <!-- Resumen del cobro anulado -->
                    <div class="mb-4 p-4 bg-slate-50 rounded-2xl border border-slate-150 space-y-2">
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest block mb-0.5">Estudiante</p>
                        <p class="text-sm font-bold text-slate-800">{{ mensualidadVerAnulada?.registro_internado?.estudiante?.persona?.nombre }} {{ mensualidadVerAnulada?.registro_internado?.estudiante?.persona?.ap_paterno }}</p>
                        <p class="text-xs text-slate-500 font-medium">Concepto/Mes: <span class="text-slate-800 font-bold">{{ mensualidadVerAnulada?.mes }}</span> | Monto original: <span class="text-slate-800 font-bold">Bs. {{ mensualidadVerAnulada?.total }}</span></p>
                    </div>

                    <!-- Visualización del motivo de anulación registrado -->
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Motivo Registrado:</label>
                        <div class="w-full bg-rose-50 rounded-xl border border-rose-100 p-4 min-h-[80px]">
                            <p class="text-sm font-medium text-rose-800 leading-relaxed whitespace-pre-wrap">
                                {{ mensualidadVerAnulada?.motivo_anulacion || 'Sin motivo especificado en la base de datos.' }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Acciones del modal -->
                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse rounded-b-3xl border-t border-slate-100">
                    <button @click="emit('close')" type="button" class="w-full inline-flex justify-center rounded-xl border border-slate-200 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 transition">
                        Cerrar Visor
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
