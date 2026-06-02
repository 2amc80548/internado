<script setup lang="ts">
// Definición de las propiedades del componente
defineProps<{
    showPagarModal: boolean;
    mensualidadSeleccionada: any;
    formPagar: any;
}>();

// Definición de los eventos emitidos por el componente
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
}>();
</script>

<template>
    <!-- Modal Registrar Pago -->
    <div v-if="showPagarModal" class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Capa de desenfoque de fondo -->
            <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="emit('close')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Contenedor del contenido del modal -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form @submit.prevent="emit('submit')">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">Registrar Pago</h3>
                        
                        <!-- Caja de resumen del estudiante y cobro -->
                        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm font-medium text-gray-900">{{ mensualidadSeleccionada?.registro_internado?.estudiante?.persona?.nombre }} {{ mensualidadSeleccionada?.registro_internado?.estudiante?.persona?.ap_paterno }}</p>
                            <p class="text-xs text-gray-500">Mes: {{ mensualidadSeleccionada?.mes }} | Monto: Bs. {{ mensualidadSeleccionada?.total }}</p>
                        </div>
                        
                        <!-- Selección Método y Fecha -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago</label>
                                <select v-model="formPagar.metodo_pago" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                    <option value="Efectivo">Efectivo Físico</option>
                                    <option value="QR">Transferencia Bancaria / QR</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Pago</label>
                                <input type="date" v-model="formPagar.fecha_pago" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm font-semibold text-gray-700">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de Confirmación y Cierre -->
                    <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse rounded-b-2xl">
                        <button type="submit" :disabled="formPagar.processing" class="w-full inline-flex justify-center rounded-lg shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ formPagar.processing ? 'Registrando...' : 'Confirmar Pago' }}
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
