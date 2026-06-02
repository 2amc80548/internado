<script setup lang="ts">
import { computed } from 'vue';

// Definición de las propiedades del componente
const props = defineProps<{
    users: any[];
    filteredUsers: any[];
    selectedUsers: number[];
}>();

// Definición de los eventos que emite el componente
const emit = defineEmits<{
    (e: 'update:selectedUsers', val: number[]): void;
    (e: 'view', user: any): void;
    (e: 'approve', id: number): void;
    (e: 'reject', id: number): void;
    (e: 'password', user: any): void;
    (e: 'edit', user: any): void;
    (e: 'delete', id: number): void;
}>();

// Propiedad computada para manejar la selección de todos los elementos pendientes en la tabla
const checkAll = computed({
    get() {
        const pendingUsers = props.filteredUsers.filter(u => u.estado_cuenta === 'Pendiente');
        return pendingUsers.length > 0 && props.selectedUsers.length === pendingUsers.length;
    },
    set(val) {
        if (val) {
            const pendingIds = props.filteredUsers.filter(u => u.estado_cuenta === 'Pendiente').map(u => u.id);
            emit('update:selectedUsers', pendingIds);
        } else {
            emit('update:selectedUsers', []);
        }
    }
});

// Función para alternar la selección individual de un usuario
const toggleSelect = (userId: number) => {
    const newSelected = [...props.selectedUsers];
    const idx = newSelected.indexOf(userId);
    if (idx > -1) {
        newSelected.splice(idx, 1);
    } else {
        newSelected.push(userId);
    }
    emit('update:selectedUsers', newSelected);
};
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-10">
                        <!-- Casilla para seleccionar/deseleccionar todos los usuarios pendientes -->
                        <input type="checkbox" v-model="checkAll" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Usuario / Persona</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Contacto</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Rol / Estado</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <!-- Iteración sobre la lista filtrada de usuarios -->
                <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50 transition" :class="{'bg-teal-50/40': selectedUsers.includes(user.id)}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <!-- Checkbox individual solo disponible si la cuenta está pendiente -->
                        <input v-if="user.estado_cuenta === 'Pendiente'" type="checkbox" :checked="selectedUsers.includes(user.id)" @change="toggleSelect(user.id)" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <!-- Foto de perfil o avatar predeterminado de acuerdo al rol -->
                                <img v-if="user.persona?.estudiante?.ruta_foto" :src="user.persona.estudiante.ruta_foto" class="h-10 w-10 rounded-full object-cover border border-gray-200 shadow-sm animate-fade-in">
                                <div v-else :class="[
                                    user.rol?.nombre === 'Superadmin' ? 'bg-indigo-100 text-indigo-800 border-indigo-200' :
                                    user.rol?.nombre === 'Encargada' ? 'bg-blue-100 text-blue-800 border-blue-200' :
                                    'bg-teal-100 text-teal-800 border-teal-200'
                                ]" class="h-10 w-10 rounded-full border flex items-center justify-center font-bold shadow-sm">
                                    {{ user.persona?.nombre?.charAt(0) || user.name.charAt(0) }}
                                </div>
                            </div>
                            <div class="ml-4">
                                <!-- Nombre completo y documento de identidad -->
                                <div class="text-sm font-bold text-gray-900">{{ user.persona?.nombre || user.name }} {{ user.persona?.ap_paterno || '' }} {{ user.persona?.ap_materno || '' }}</div>
                                <div class="text-xs text-gray-500">CI: {{ user.persona?.ci || 'S/CI' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <!-- Información de contacto del usuario -->
                        <div class="text-sm text-gray-900">Cel: {{ user.persona?.celular || 'S/N' }}</div>
                        <div class="text-xs text-gray-500">{{ user.email }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col space-y-1">
                            <!-- Badges del rol de sistema -->
                            <span :class="[
                                user.rol?.nombre === 'Superadmin' ? 'bg-indigo-100 text-indigo-800 border-indigo-200' :
                                user.rol?.nombre === 'Encargada' ? 'bg-blue-100 text-blue-800 border-blue-200' :
                                'bg-teal-100 text-teal-800 border-teal-200'
                            ]" class="px-2.5 py-0.5 inline-flex text-[10px] leading-4 font-black rounded-full border self-start uppercase tracking-wide">
                                {{ user.rol?.nombre || 'Sin Rol' }}
                            </span>
                            
                            <!-- Badges del estado de la cuenta -->
                            <span v-if="user.estado_cuenta === 'Pendiente'" class="px-2 py-0.5 inline-flex text-[10px] leading-4 font-bold rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200 self-start">
                                ⏳ Pendiente Aprobación
                            </span>
                            <span v-else-if="user.estado_cuenta === 'Rechazado'" class="px-2 py-0.5 inline-flex text-[10px] leading-4 font-bold rounded-full bg-red-50 text-red-700 border border-red-200 self-start">
                                ❌ Solicitud Rechazada
                            </span>
                            <span v-else class="px-2 py-0.5 inline-flex text-[10px] leading-4 font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 self-start">
                                ✅ Cuenta Activa
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex justify-end items-center gap-3">
                        
                        <!-- Botón para ver la ficha completa del usuario -->
                        <button @click="emit('view', user)" class="text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 p-2 rounded-xl transition duration-150" title="Ver Detalles de la Cuenta">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>

                        <!-- Botones de aprobación/rechazo para solicitudes pendientes -->
                        <template v-if="user.estado_cuenta === 'Pendiente'">
                            <button @click="emit('approve', user.id)" class="text-emerald-600 hover:text-white bg-emerald-50 hover:bg-emerald-600 p-2 rounded-xl border border-emerald-100 transition duration-150" title="Aprobar Solicitud de Acceso">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </button>
                            <button @click="emit('reject', user.id)" class="text-red-600 hover:text-white bg-red-50 hover:bg-red-600 p-2 rounded-xl border border-red-100 transition duration-150" title="Rechazar Solicitud de Acceso">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </template>

                        <!-- Controles de edición y contraseña para cuentas activas -->
                        <template v-if="user.estado_cuenta !== 'Rechazado'">
                            <button @click="emit('password', user)" class="text-indigo-600 hover:text-white bg-indigo-50 hover:bg-indigo-600 p-2 rounded-xl border border-indigo-100 transition duration-150" title="Blanquear o Cambiar Contraseña">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                            </button>
                            <button @click="emit('edit', user)" class="text-teal-600 hover:text-white bg-teal-50 hover:bg-teal-600 p-2 rounded-xl border border-teal-100 transition duration-150" title="Modificar Datos Básicos">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>
                        </template>
                        
                        <!-- Botón para eliminar físicamente la cuenta de acceso -->
                        <button @click="emit('delete', user.id)" class="text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 p-2 rounded-xl border border-rose-100 transition duration-150" title="Eliminar Acceso al Sistema">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </td>
                </tr>
                <tr v-if="filteredUsers.length === 0">
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                        <span class="text-4xl block mb-2">🔍</span> No se encontraron cuentas de usuario con los criterios seleccionados.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
