<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    roles: Array,
    permisos: Array
});

// Función simple para verificar si el rol tiene el permiso asociado
const hasPermiso = (rol: any, permisoId: number) => {
    return rol.permisos.some((p: any) => p.id === permisoId);
};
</script>

<template>
    <Head title="Roles y Permisos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                Control de Seguridad y Accesos
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="mb-8 p-8 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-2xl shadow-lg text-white">
                    <h3 class="text-3xl font-bold mb-2">Roles del Sistema</h3>
                    <p class="text-purple-100 text-lg">Esta es la matriz de permisos globales. El usuario <strong>Superadmin</strong> tiene acceso irrestricto, mientras que la Encargada y Estudiantes tienen accesos limitados según la tabla inferior.</p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-bold text-gray-900 uppercase tracking-wider border-r border-gray-200 w-1/4">
                                        Lista de Permisos
                                    </th>
                                    <!-- Cabecera dinámica de roles -->
                                    <th v-for="rol in roles" :key="rol.id" scope="col" class="px-6 py-4 text-center text-sm font-bold text-gray-900 uppercase tracking-wider w-1/4">
                                        {{ rol.nombre }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- Filas dinámicas de permisos -->
                                <tr v-for="permiso in permisos" :key="permiso.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                                        <div class="text-sm font-medium text-gray-900">{{ permiso.nombre }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ permiso.descripcion }}</div>
                                    </td>
                                    
                                    <!-- Verificación por rol -->
                                    <td v-for="rol in roles" :key="rol.id" class="px-6 py-4 whitespace-nowrap text-center">
                                        <span v-if="hasPermiso(rol, permiso.id) || rol.nombre === 'Superadmin'" class="inline-flex justify-center items-center w-8 h-8 rounded-full bg-green-100 text-green-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </span>
                                        <span v-else class="inline-flex justify-center items-center w-8 h-8 rounded-full bg-red-100 text-red-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
