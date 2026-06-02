<script setup lang="ts">
// Definición de las propiedades del componente
defineProps<{
    showAnularModal: boolean;
    mensualidadAAnular: any;
    formAnular: any;
}>();

// Definición de los eventos emitidos por el componente
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
}>();
</script>

<template>
    <!-- Modal Anular Pago -->
    <div v-if="showAnularModal" class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Capa de desenfoque de fondo -->
            <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="emit('close')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Contenedor del contenido del modal -->
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100">
                <form @submit.prevent="emit('submit')">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-red-100 text-red-700 rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <h3 class="text-lg font-black text-slate-800 leading-tight">Anular Transacción</h3>
                        </div>
                        
                        <!-- Caja de resumen del cobro a anular -->
                        <div class="mb-4 p-4 bg-red-50 rounded-2xl border border-red-100">
                            <p class="text-sm font-bold text-red-900">{{ mensualidadAAnular?.registro_internado?.estudiante?.persona?.nombre }} {{ mensualidadAAnular?.registro_internado?.estudiante?.persona?.ap_paterno }}</p>
                            <p class="text-xs text-red-700 font-medium">Mes: {{ mensualidadAAnular?.mes }} | Monto original: Bs. {{ mensualidadAAnular?.total }}</p>
                        </div>

                        <!-- Campos para Captura de Motivo y Contraseña -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Motivo de Anulación</label>
                                <textarea v-model="formAnular.motivo_anulacion" rows="3" required placeholder="Escriba detalladamente el motivo de la anulación..." class="w-full rounded-xl border-gray-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm font-medium text-slate-700"></textarea>
                                <p v-if="formAnular.errors.motivo_anulacion" class="text-xs text-red-600 mt-1 font-bold">{{ formAnular.errors.motivo_anulacion }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Contraseña del Administrador</label>
                                <input v-model="formAnular.password" type="password" required placeholder="Ingrese su contraseña..." class="w-full rounded-xl border-gray-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm font-medium text-slate-700">
                                <p v-if="formAnular.errors.password" class="text-xs text-red-600 mt-1 font-bold">{{ formAnular.errors.password }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de Confirmación de Anulación -->
                    <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse rounded-b-3xl border-t border-slate-100">
                        <button type="submit" :disabled="formAnular.processing" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-red-600 text-sm font-bold text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto transition">
                            {{ formAnular.processing ? 'Anulando...' : 'Confirmar Anulación' }}
                        </button>
                        <button @click="emit('close')" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-200 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 sm:mt-0 sm:ml-3 sm:w-auto transition">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
