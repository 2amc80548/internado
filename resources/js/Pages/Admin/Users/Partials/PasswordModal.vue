<script setup lang="ts">
// Definición de las propiedades del componente
defineProps<{
    showPasswordModal: boolean;
    userForPassword: any;
    formPassword: any;
}>();

// Definición de los eventos que emite el componente
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
}>();
</script>

<template>
    <!-- Modal para Cambiar o Restablecer Contraseña -->
    <div v-if="showPasswordModal" class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Capa de desenfoque de fondo -->
            <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="emit('close')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Contenedor del modal de contraseña -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form @submit.prevent="emit('submit')">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <h3 class="text-lg leading-6 font-black text-gray-900 mb-4 border-b pb-3 flex items-center gap-2">
                            <span>🔑</span> Restablecer Contraseña
                        </h3>
                        
                        <div class="space-y-4">
                            <!-- Información breve del usuario destino -->
                            <p class="text-xs text-gray-500">
                                Estás cambiando las credenciales del usuario: <br>
                                <strong class="text-slate-700 font-bold text-sm">{{ userForPassword?.persona?.nombre || userForPassword?.name }} {{ userForPassword?.persona?.ap_paterno || '' }}</strong>
                            </p>

                            <!-- Campo Nueva Contraseña -->
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Nueva Contraseña (mínimo 8 caracteres)</label>
                                <input v-model="formPassword.password" type="password" required class="mt-1 block w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500 shadow-sm">
                                <p v-if="formPassword.errors.password" class="text-red-500 text-xs mt-1">{{ formPassword.errors.password }}</p>
                            </div>

                            <!-- Campo Confirmar Contraseña -->
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Confirmar Nueva Contraseña</label>
                                <input v-model="formPassword.password_confirmation" type="password" required class="mt-1 block w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500 shadow-sm">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de confirmación y cierre -->
                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t gap-2">
                        <button type="submit" :disabled="formPassword.processing" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-sm font-bold text-white transition sm:ml-3 sm:w-auto">
                            Actualizar Contraseña
                        </button>
                        <button type="button" @click="emit('close')" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white hover:bg-gray-50 text-sm font-bold text-gray-700 transition sm:mt-0 sm:w-auto">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
