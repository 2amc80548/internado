<script setup lang="ts">
// Definición de las propiedades del componente
defineProps<{
    showDeleteModal: boolean;
    deletePassword: string;
    deleteError: string;
    deleteFormProcessing: boolean;
}>();

// Definición de los eventos que emite el componente
const emit = defineEmits<{
    (e: 'update:deletePassword', val: string): void;
    (e: 'close'): void;
    (e: 'submit'): void;
}>();
</script>

<template>
    <!-- Modal para Confirmar la Eliminación de Cuentas -->
    <div v-if="showDeleteModal" class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Capa de desenfoque de fondo -->
            <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="emit('close')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Contenedor del modal de eliminación segura -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="bg-white px-6 pt-6 pb-4">
                    <h3 class="text-lg leading-6 font-black text-rose-700 mb-4 border-b pb-3 flex items-center gap-2">
                        <span>⚠️</span> Confirmar Eliminación de Acceso
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Advertencia legal/operativa -->
                        <p class="text-xs text-gray-600 font-semibold leading-relaxed">
                            ¿Está completamente seguro de eliminar esta cuenta de acceso? Esta acción removerá el login del usuario del sistema.
                        </p>
                        <blockquote class="border-l-4 border-rose-500 bg-rose-50 p-2.5 rounded-r-lg text-xs text-rose-800 font-bold">
                            Nota: Si la cuenta pertenece a un estudiante, el expediente físico y el historial de mensualidades permanecerán intactos en la sección de "Estudiantes".
                        </blockquote>

                        <!-- Campo Contraseña de Confirmación de Administrador -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Introduce tu Contraseña de Administrador</label>
                            <input :value="deletePassword" @input="emit('update:deletePassword', ($event.target as HTMLInputElement).value)" type="password" required placeholder="Contraseña de Superadmin" class="mt-1 block w-full border-gray-300 rounded-lg text-sm focus:ring-rose-500 focus:border-rose-500 shadow-sm">
                            <p v-if="deleteError" class="text-rose-600 text-xs mt-1.5 font-bold">{{ deleteError }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Botones del modal de eliminación -->
                <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t gap-2">
                    <button type="button" @click="emit('submit')" :disabled="deleteFormProcessing" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-sm font-bold text-white transition sm:ml-3 sm:w-auto">
                        {{ deleteFormProcessing ? 'Eliminando...' : 'Eliminar Acceso' }}
                    </button>
                    <button type="button" @click="emit('close')" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white hover:bg-gray-50 text-sm font-bold text-gray-700 transition sm:mt-0 sm:w-auto">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
