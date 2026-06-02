<script setup lang="ts">
// Definición de las propiedades del componente
defineProps<{
    showModal: boolean;
    isEditing: boolean;
    roles: any[];
    form: any;
}>();

// Definición de los eventos que emite el componente
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
}>();
</script>

<template>
    <!-- Modal principal de CRUD de Usuario -->
    <div v-if="showModal" class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Capa de desenfoque de fondo -->
            <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="emit('close')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Contenedor del formulario modal -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form @submit.prevent="emit('submit')">
                    <div class="bg-white px-6 pt-6 pb-4 sm:p-6">
                        <!-- Título dinámico según se esté creando o editando -->
                        <h3 class="text-xl leading-6 font-black text-gray-900 mb-6 border-b pb-4 flex items-center gap-2">
                            <span>⚙️</span> {{ isEditing ? 'Editar Datos de Acceso' : 'Nuevo Usuario Administrativo/Encargado' }}
                        </h3>

                        <!-- Alerta visual si existen errores de validación en el formulario -->
                        <div v-if="Object.keys(form.errors).length > 0" class="mb-6 bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-xl">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-semibold text-rose-800">Hay errores de validación en el formulario:</h3>
                                    <ul class="mt-1 list-disc list-inside text-xs text-rose-700">
                                        <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Campos organizados del formulario -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Sección 1: Información Personal -->
                            <div class="sm:col-span-2">
                                <h4 class="font-bold text-teal-700 mb-1 uppercase text-xs tracking-wider border-b pb-1">1. Información Personal</h4>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Carnet de Identidad (CI)</label>
                                <input v-model="form.ci" :disabled="isEditing" type="text" placeholder="Ej. 1234567" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <p v-if="form.errors.ci" class="text-rose-500 text-xs mt-1 font-semibold">{{ form.errors.ci }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Nombres</label>
                                <input v-model="form.nombre" type="text" placeholder="Nombres" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <p v-if="form.errors.nombre" class="text-rose-500 text-xs mt-1 font-semibold">{{ form.errors.nombre }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Apellido Paterno</label>
                                <input v-model="form.ap_paterno" type="text" placeholder="Paterno" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <p v-if="form.errors.ap_paterno" class="text-rose-500 text-xs mt-1 font-semibold">{{ form.errors.ap_paterno }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Apellido Materno</label>
                                <input v-model="form.ap_materno" type="text" placeholder="Materno" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Celular</label>
                                <input v-model="form.celular" type="text" placeholder="Celular o Teléfono" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Género (Sexo)</label>
                                <select v-model="form.sexo" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                    <option value="">Seleccione...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </select>
                            </div>

                            <!-- Sección 2: Información de Cuentas y Roles -->
                            <div class="sm:col-span-2 mt-2">
                                <h4 class="font-bold text-teal-700 mb-1 uppercase text-xs tracking-wider border-b pb-1">2. Credenciales y Permisos de Sistema</h4>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Correo Electrónico (Opcional)</label>
                                <input v-model="form.email" type="email" placeholder="usuario@internado.org (opcional)" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <p v-if="form.errors.email" class="text-rose-500 text-xs mt-1 font-semibold">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Rol Asignado</label>
                                <select v-model="form.rol_id" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500 bg-blue-50/50 border-blue-200">
                                    <option disabled value="">Seleccionar Rol...</option>
                                    <option v-for="rol in roles" :key="rol.id" :value="rol.id">{{ rol.nombre }}</option>
                                </select>
                                <p v-if="form.errors.rol_id" class="text-rose-500 text-xs mt-1 font-semibold">{{ form.errors.rol_id }}</p>
                            </div>

                            <!-- Aviso de contraseña inicial para cuentas nuevas -->
                            <div v-if="!isEditing" class="sm:col-span-2 bg-slate-50 p-4 rounded-xl border border-slate-200">
                                <p class="text-xs text-slate-600 font-semibold flex items-center gap-1.5">
                                    <span>💡</span> Para cuentas nuevas, la contraseña se establecerá por defecto igual al <strong>Número de Cédula de Identidad (CI)</strong> ingresado. El usuario podrá cambiarla al acceder a su perfil.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer del modal con botones de acción -->
                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t gap-2">
                        <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-sm font-bold text-white transition focus:outline-none sm:ml-3 sm:w-auto">
                            {{ form.processing ? 'Guardando...' : 'Guardar Usuario' }}
                        </button>
                        <button type="button" @click="emit('close')" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white hover:bg-gray-50 text-sm font-bold text-gray-700 transition focus:outline-none sm:mt-0 sm:w-auto">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
