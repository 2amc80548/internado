<script setup lang="ts">
// Definición de las propiedades del componente
defineProps<{
    show: boolean;
    form: any;
}>();

// Definición de los eventos emitidos por el componente
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
}>();
</script>

<template>
    <!-- MODAL: ELIMINAR ESTUDIANTE SEGURO -->
    <div v-if="show" class="fixed z-[60] inset-0 overflow-y-auto animate-fade-in">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Capa de desenfoque de fondo -->
            <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/60" @click="emit('close')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Contenedor del contenido del modal -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-red-100">
                <form @submit.prevent="emit('submit')">
                    <!-- Título y advertencia del proceso -->
                    <div class="bg-red-50/50 p-6 border-b border-red-100">
                        <h3 class="text-lg font-bold text-red-800 flex items-center gap-2">
                            <span>🗑️</span> Eliminación Permanente de Estudiante
                        </h3>
                        <p class="text-xs text-red-600 mt-1 font-semibold">
                            Esta acción es irreversible. Se eliminará la ficha, historial de notas, mensualidades y cuenta del estudiante de forma definitiva.
                        </p>
                    </div>
                    
                    <!-- Cuerpo del modal -->
                    <div class="bg-white p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wide">Estudiante a Eliminar</label>
                            <p class="text-sm font-bold text-slate-800 mt-1">{{ form.nombre_completo }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                                Contraseña de Administrador <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.password" type="password" required placeholder="Ingrese su contraseña actual para confirmar" class="w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-red-500 focus:border-red-500">
                            <p v-if="form.errors.anular_password" class="text-rose-500 text-xs mt-1">{{ form.errors.anular_password }}</p>
                        </div>
                    </div>
                    
                    <!-- Botones de Confirmación y Cancelación -->
                    <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse border-t gap-2">
                        <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-red-600 hover:bg-red-700 text-sm font-bold text-white transition sm:ml-3 sm:w-auto">
                            {{ form.processing ? 'Eliminando...' : 'Confirmar Eliminación' }}
                        </button>
                        <button type="button" @click="emit('close')" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto transition">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
