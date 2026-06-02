<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    users: any[];
    roles: any[];
}>();

const showModal = ref(false);
const isEditing = ref(false);
const isReadOnlyMode = ref(true);
const showPasswordModal = ref(false);
const userForPassword = ref<any>(null);
const showViewModal = ref(false);
const selectedUser = ref<any>(null);

// Filtros de búsqueda
const filter = ref({
    texto: '',
    rol_id: '',
    estado_cuenta: '',
    sexo: ''
});

const selectedUsers = ref<number[]>([]);
const checkAll = computed({
    get() {
        const pendingUsers = filteredUsers.value.filter(u => u.estado_cuenta === 'Pendiente');
        return pendingUsers.length > 0 && selectedUsers.value.length === pendingUsers.length;
    },
    set(val) {
        if (val) {
            selectedUsers.value = filteredUsers.value.filter(u => u.estado_cuenta === 'Pendiente').map(u => u.id);
        } else {
            selectedUsers.value = [];
        }
    }
});

const form = useForm({
    id: null as number | null,
    ci: '',
    nombre: '',
    ap_paterno: '',
    ap_materno: '',
    email: '',
    celular: '',
    sexo: '',
    rol_id: '' as number | string,
    estado_cuenta: ''
});

const filteredUsers = computed(() => {
    return props.users.filter(user => {
        // Texto general (Nombre, CI, Celular)
        let matchesText = true;
        if (filter.value.texto) {
            const q = filter.value.texto.toLowerCase();
            const nombre = `${user.persona?.nombre} ${user.persona?.ap_paterno} ${user.persona?.ap_materno}`.toLowerCase();
            const ci = user.persona?.ci?.toLowerCase() || '';
            const celular = user.persona?.celular?.toLowerCase() || '';
            matchesText = nombre.includes(q) || ci.includes(q) || celular.includes(q);
        }

        let matchesRole = filter.value.rol_id === '' || String(user.rol_id) === String(filter.value.rol_id);
        
        let matchesEstado = true;
        if (filter.value.estado_cuenta !== '') {
            matchesEstado = user.estado_cuenta === filter.value.estado_cuenta;
        }
        
        let matchesSexo = true;
        if (filter.value.sexo) {
            matchesSexo = user.persona?.sexo === filter.value.sexo;
        }

        return matchesText && matchesRole && matchesEstado && matchesSexo;
    });
});

const countHombres = computed(() => {
    return filteredUsers.value.filter(u => u.persona?.sexo === 'M').length;
});

const countMujeres = computed(() => {
    return filteredUsers.value.filter(u => u.persona?.sexo === 'F').length;
});

const countPendientes = computed(() => {
    return props.users.filter(u => u.estado_cuenta === 'Pendiente').length;
});

const openCreateModal = () => {
    isEditing.value = false;
    isReadOnlyMode.value = false;
    form.reset();
    showModal.value = true;
};

const openEditModal = (user: any) => {
    isEditing.value = true;
    isReadOnlyMode.value = false;
    form.reset();
    form.clearErrors();
    
    form.id = user.id;
    form.ci = user.persona?.ci || '';
    form.nombre = user.persona?.nombre || '';
    form.ap_paterno = user.persona?.ap_paterno || '';
    form.ap_materno = user.persona?.ap_materno || '';
    form.celular = user.persona?.celular || '';
    form.sexo = user.persona?.sexo || '';
    form.email = user.email || '';
    form.rol_id = user.rol_id || '';
    form.estado_cuenta = user.estado_cuenta || '';
    
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submitForm = () => {
    if (!confirm('¿Está seguro de que desea guardar los cambios?')) {
        return;
    }
    if (isEditing.value) {
        form.put(route('users.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const exportToExcel = () => {
    const list = filteredUsers.value;
    if (list.length === 0) {
        alert('No hay usuarios para exportar con los filtros actuales.');
        return;
    }

    const headers = [
        'C.I.',
        'Nombre Completo',
        'Email',
        'Rol',
        'Celular',
        'Género',
        'Estado Cuenta'
    ];

    const rows = list.map(u => {
        return [
            u.persona?.ci || '',
            `${u.persona?.nombre || ''} ${u.persona?.ap_paterno || ''} ${u.persona?.ap_materno || ''}`.trim(),
            u.email || '',
            u.rol?.nombre || '',
            u.persona?.celular || '',
            u.persona?.sexo === 'M' ? 'Masculino' : (u.persona?.sexo === 'F' ? 'Femenino' : 'No especificado'),
            u.estado_cuenta || ''
        ];
    });

    const csvContent = [
        headers.join(';'),
        ...rows.map(row => row.map(val => {
            const strVal = String(val).replace(/;/g, ',').replace(/"/g, '""');
            return `"${strVal}"`;
        }).join(';'))
    ].join('\r\n');

    const BOM = '\uFEFF';
    const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
    
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `reporte_usuarios_sistema_${new Date().toISOString().substring(0,10)}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const openViewModal = (user: any) => {
    selectedUser.value = user;
    showViewModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedUser.value = null;
};

// Eliminar con Contraseña
const showDeleteModal = ref(false);
const userIdToDelete = ref<number | null>(null);
const deletePassword = ref('');
const deleteError = ref('');
const deleteFormProcessing = ref(false);

const confirmDeleteUser = (id: number) => {
    userIdToDelete.value = id;
    deletePassword.value = '';
    deleteError.value = '';
    showDeleteModal.value = true;
};

const submitDeleteUser = () => {
    if (!deletePassword.value) {
        deleteError.value = 'Debe introducir su contraseña para continuar.';
        return;
    }

    deleteFormProcessing.value = true;
    deleteError.value = '';

    router.delete(route('users.destroy', userIdToDelete.value), {
        data: { password: deletePassword.value },
        onError: (errors) => {
            deleteFormProcessing.value = false;
            if (errors.delete_password) {
                deleteError.value = errors.delete_password;
            } else {
                deleteError.value = 'Contraseña incorrecta u otro error en el servidor.';
            }
        },
        onSuccess: () => {
            deleteFormProcessing.value = false;
            showDeleteModal.value = false;
            userIdToDelete.value = null;
            deletePassword.value = '';
        }
    });
};

const formPassword = useForm({
    password: '',
    password_confirmation: ''
});

const openPasswordModal = (user: any) => {
    userForPassword.value = user;
    formPassword.reset();
    showPasswordModal.value = true;
};

const submitPassword = () => {
    formPassword.post(route('users.password', userForPassword.value.id), {
        onSuccess: () => {
            showPasswordModal.value = false;
            alert('Contraseña actualizada exitosamente.');
        }
    });
};

const rechazarUsuario = (id: number) => {
    if (confirm('¿Estás seguro de que deseas rechazar la solicitud de acceso de este usuario?')) {
        router.put(route('users.rechazar', id));
    }
};

const aprobarCuentaIndividual = (id: number) => {
    if (confirm('¿Estás seguro de aprobar la cuenta de este usuario?')) {
        router.put(route('users.aprobar', id));
    }
};

const aprobarMasivo = () => {
    if (confirm(`¿Aprobar las ${selectedUsers.value.length} cuentas de usuario seleccionadas?`)) {
        router.post(route('users.aprobar.masivo'), { user_ids: selectedUsers.value }, {
            onSuccess: () => {
                selectedUsers.value = [];
                alert('Cuentas aprobadas exitosamente.');
            }
        });
    }
};
</script>

<template>
    <Head title="Usuarios y Roles" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                Gestión de Usuarios y Roles
            </h2>
        </template>

        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                


                <!-- Barra de Super Filtros -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-800">Filtros y Búsqueda</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-slate-100 text-slate-800 border border-slate-200 uppercase tracking-wide">
                                    Filtrados: {{ filteredUsers.length }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">Busca y administra accesos de Superadministradores, Encargadas y Estudiantes habilitados.</p>
                        </div>
                        <div class="flex items-center gap-3 w-full md:w-auto justify-end flex-wrap">
                            <button v-if="selectedUsers.length > 0" @click="aprobarMasivo" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-6 rounded-xl shadow-sm transition flex items-center gap-2 text-sm transform hover:scale-102">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Aprobar {{ selectedUsers.length }} Solicitudes
                            </button>
                            <button @click="exportToExcel" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-5 rounded-xl shadow-md transition text-sm flex items-center gap-2 transform hover:scale-102" title="Descargar reporte Excel filtrado">
                                <svg class="w-4 h-4 text-indigo-100" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-4H8V8h5v2z"/>
                                </svg>
                                📊 Descargar Excel
                            </button>
                            <button @click="openCreateModal" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded-xl shadow-md transition text-sm transform hover:scale-102">
                                + Nuevo Usuario
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Buscar por Texto</label>
                            <div class="relative mt-1">
                                <input v-model="filter.texto" type="text" placeholder="CI, Nombre, Celular..." class="w-full pl-8 pr-3 py-2 border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Estado de Cuenta</label>
                            <select v-model="filter.estado_cuenta" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Todos los Estados</option>
                                <option value="Pendiente">Pendiente de Aprobación</option>
                                <option value="Aprobado">Aprobados / Activos</option>
                                <option value="Rechazado">Rechazados</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Rol de Sistema</label>
                            <select v-model="filter.rol_id" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Todos los Roles</option>
                                <option v-for="rol in roles" :key="rol.id" :value="rol.id">{{ rol.nombre }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Género (Sexo)</label>
                            <select v-model="filter.sexo" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Todos</option>
                                <option value="M">Hombre / Masculino</option>
                                <option value="F">Mujer / Femenino</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Resultados -->
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-10">
                                        <input type="checkbox" v-model="checkAll" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Usuario / Persona</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Contacto</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Rol / Estado</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50 transition" :class="{'bg-teal-50/40': selectedUsers.includes(user.id)}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input v-if="user.estado_cuenta === 'Pendiente'" type="checkbox" :value="user.id" v-model="selectedUsers" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
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
                                                <div class="text-sm font-bold text-gray-900">{{ user.persona?.nombre || user.name }} {{ user.persona?.ap_paterno || '' }} {{ user.persona?.ap_materno || '' }}</div>
                                                <div class="text-xs text-gray-500">CI: {{ user.persona?.ci || 'S/CI' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">Cel: {{ user.persona?.celular || 'S/N' }}</div>
                                        <div class="text-xs text-gray-500">{{ user.email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col space-y-1">
                                            <span :class="[
                                                user.rol?.nombre === 'Superadmin' ? 'bg-indigo-100 text-indigo-800 border-indigo-200' :
                                                user.rol?.nombre === 'Encargada' ? 'bg-blue-100 text-blue-800 border-blue-200' :
                                                'bg-teal-100 text-teal-800 border-teal-200'
                                            ]" class="px-2.5 py-0.5 inline-flex text-[10px] leading-4 font-black rounded-full border self-start uppercase tracking-wide">
                                                {{ user.rol?.nombre || 'Sin Rol' }}
                                            </span>
                                            
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
                                        
                                        <!-- Ficha rápida de usuario -->
                                        <button @click="openViewModal(user)" class="text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 p-2 rounded-xl transition duration-150" title="Ver Detalles de la Cuenta">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </button>

                                        <!-- Botones para solicitudes pendientes de Estudiantes -->
                                        <template v-if="user.estado_cuenta === 'Pendiente'">
                                            <button @click="aprobarCuentaIndividual(user.id)" class="text-emerald-600 hover:text-white bg-emerald-50 hover:bg-emerald-600 p-2 rounded-xl border border-emerald-100 transition duration-150" title="Aprobar Solicitud de Acceso">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                            <button @click="rechazarUsuario(user.id)" class="text-red-600 hover:text-white bg-red-50 hover:bg-red-600 p-2 rounded-xl border border-red-100 transition duration-150" title="Rechazar Solicitud de Acceso">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </template>

                                        <!-- Controles estándar para cuentas de acceso activas -->
                                        <template v-if="user.estado_cuenta !== 'Rechazado'">
                                            <button @click="openPasswordModal(user)" class="text-indigo-600 hover:text-white bg-indigo-50 hover:bg-indigo-600 p-2 rounded-xl border border-indigo-100 transition duration-150" title="Blanquear o Cambiar Contraseña">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                            </button>
                                            <button @click="openEditModal(user)" class="text-teal-600 hover:text-white bg-teal-50 hover:bg-teal-600 p-2 rounded-xl border border-teal-100 transition duration-150" title="Modificar Datos Básicos">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                        </template>
                                        
                                        <!-- Eliminar cuenta del sistema -->
                                        <button @click="confirmDeleteUser(user.id)" class="text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 p-2 rounded-xl border border-rose-100 transition duration-150" title="Eliminar Acceso al Sistema">
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
                </div>

                <!-- Modal CRUD de Usuario -->
                <div v-if="showModal" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="closeModal"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">

                            <form @submit.prevent="submitForm">
                                <div class="bg-white px-6 pt-6 pb-4 sm:p-6">
                                    <h3 class="text-xl leading-6 font-black text-gray-900 mb-6 border-b pb-4 flex items-center gap-2">
                                        <span>⚙️</span> {{ isEditing ? 'Editar Datos de Acceso' : 'Nuevo Usuario Administrativo/Encargado' }}
                                    </h3>

                                    <!-- Alerta de Errores de Validación -->
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

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                        <!-- DATOS PERSONALES -->
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

                                        <!-- ACCESO Y ROLES -->
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

                                        <div v-if="!isEditing" class="sm:col-span-2 bg-slate-50 p-4 rounded-xl border border-slate-200">
                                            <p class="text-xs text-slate-600 font-semibold flex items-center gap-1.5">
                                                <span>💡</span> Para cuentas nuevas, la contraseña se establecerá por defecto igual al <strong>Número de Cédula de Identidad (CI)</strong> ingresado. El usuario podrá cambiarla al acceder a su perfil.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t gap-2">
                                    <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-sm font-bold text-white transition focus:outline-none sm:ml-3 sm:w-auto">
                                        {{ form.processing ? 'Guardando...' : 'Guardar Usuario' }}
                                    </button>
                                    <button type="button" @click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white hover:bg-gray-50 text-sm font-bold text-gray-700 transition focus:outline-none sm:mt-0 sm:w-auto">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Ver Detalle (Ficha Resumen) -->
                <div v-if="showViewModal" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="closeViewModal"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                            <div class="relative bg-gradient-to-r from-teal-500 to-indigo-600 h-28">
                                <button @click="closeViewModal" class="absolute top-4 right-4 bg-white/20 text-white hover:bg-white/40 p-2 rounded-full transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                                <div class="absolute -bottom-10 left-6">
                                    <div class="w-20 h-20 rounded-2xl bg-white p-1 shadow-md">
                                        <img v-if="selectedUser?.persona?.estudiante?.ruta_foto" :src="selectedUser.persona.estudiante.ruta_foto" class="w-full h-full rounded-xl object-cover border border-gray-100 shadow-sm">
                                        <div v-else class="w-full h-full rounded-xl bg-teal-100 flex items-center justify-center font-black text-2xl text-teal-800 border">
                                            {{ selectedUser?.persona?.nombre?.charAt(0) || selectedUser?.name.charAt(0) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white px-6 pt-14 pb-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-xl font-black text-slate-800 leading-tight">
                                            {{ selectedUser?.persona?.nombre || selectedUser?.name }} {{ selectedUser?.persona?.ap_paterno || '' }} {{ selectedUser?.persona?.ap_materno || '' }}
                                        </h3>
                                        <span class="inline-block mt-1.5 px-3 py-0.5 rounded-full text-xs font-black bg-indigo-100 text-indigo-800 uppercase border border-indigo-200 tracking-wide">
                                            {{ selectedUser?.rol?.nombre || 'Sin Rol' }}
                                        </span>
                                    </div>
                                    <span :class="[
                                        selectedUser?.estado_cuenta === 'Aprobado' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-yellow-100 text-yellow-800 border-yellow-200'
                                    ]" class="px-3 py-0.5 rounded-full text-xs font-bold border uppercase tracking-wider">
                                        {{ selectedUser?.estado_cuenta }}
                                    </span>
                                </div>

                                <div class="border-t border-slate-100 pt-4 mt-4 space-y-3.5">
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500 font-bold">Documento de Identidad (CI):</span>
                                        <span class="text-slate-800 font-semibold">{{ selectedUser?.persona?.ci || 'N/D' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500 font-bold">Correo de Acceso:</span>
                                        <span class="text-slate-800 font-semibold truncate max-w-[280px]">{{ selectedUser?.email }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500 font-bold">Celular / Teléfono:</span>
                                        <span class="text-slate-800 font-semibold">{{ selectedUser?.persona?.celular || 'Sin Número Registrado' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500 font-bold">Género (Sexo):</span>
                                        <span class="text-slate-800 font-semibold">
                                            {{ selectedUser?.persona?.sexo === 'M' ? 'Masculino' : (selectedUser?.persona?.sexo === 'F' ? 'Femenino' : 'No Especificado') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-slate-50 px-6 py-4 border-t flex justify-end gap-2">
                                <button @click="closeViewModal" class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-sm rounded-xl transition">
                                    Cerrar Ficha
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Cambiar Contraseña -->
                <div v-if="showPasswordModal" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="showPasswordModal = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                            <form @submit.prevent="submitPassword">
                                <div class="bg-white px-6 pt-6 pb-4">
                                    <h3 class="text-lg leading-6 font-black text-gray-900 mb-4 border-b pb-3 flex items-center gap-2">
                                        <span>🔑</span> Restablecer Contraseña
                                    </h3>
                                    
                                    <div class="space-y-4">
                                        <p class="text-xs text-gray-500">
                                            Estás cambiando las credenciales del usuario: <br>
                                            <strong class="text-slate-700 font-bold text-sm">{{ userForPassword?.persona?.nombre || userForPassword?.name }} {{ userForPassword?.persona?.ap_paterno || '' }}</strong>
                                        </p>

                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Nueva Contraseña (mínimo 8 caracteres)</label>
                                            <input v-model="formPassword.password" type="password" required class="mt-1 block w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500 shadow-sm">
                                            <p v-if="formPassword.errors.password" class="text-red-500 text-xs mt-1">{{ formPassword.errors.password }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Confirmar Nueva Contraseña</label>
                                            <input v-model="formPassword.password_confirmation" type="password" required class="mt-1 block w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500 shadow-sm">
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t gap-2">
                                    <button type="submit" :disabled="formPassword.processing" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-sm font-bold text-white transition sm:ml-3 sm:w-auto">
                                        Actualizar Contraseña
                                    </button>
                                    <button type="button" @click="showPasswordModal = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white hover:bg-gray-50 text-sm font-bold text-gray-700 transition sm:mt-0 sm:w-auto">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Eliminar Cuenta con Contraseña de Seguridad -->
                <div v-if="showDeleteModal" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="showDeleteModal = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                            <div class="bg-white px-6 pt-6 pb-4">
                                <h3 class="text-lg leading-6 font-black text-rose-700 mb-4 border-b pb-3 flex items-center gap-2">
                                    <span>⚠️</span> Confirmar Eliminación de Acceso
                                </h3>
                                
                                <div class="space-y-4">
                                    <p class="text-xs text-gray-600 font-semibold leading-relaxed">
                                        ¿Está completamente seguro de eliminar esta cuenta de acceso? Esta acción removerá el login del usuario del sistema.
                                    </p>
                                    <blockquote class="border-l-4 border-rose-500 bg-rose-50 p-2.5 rounded-r-lg text-xs text-rose-800 font-bold">
                                        Nota: Si la cuenta pertenece a un estudiante, el expediente físico y el historial de mensualidades permanecerán intactos en la sección de "Estudiantes".
                                    </blockquote>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Introduce tu Contraseña de Administrador</label>
                                        <input v-model="deletePassword" type="password" required placeholder="Contraseña de Superadmin" class="mt-1 block w-full border-gray-300 rounded-lg text-sm focus:ring-rose-500 focus:border-rose-500 shadow-sm">
                                        <p v-if="deleteError" class="text-rose-600 text-xs mt-1.5 font-bold">{{ deleteError }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t gap-2">
                                <button type="button" @click="submitDeleteUser" :disabled="deleteFormProcessing" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-sm font-bold text-white transition sm:ml-3 sm:w-auto">
                                    {{ deleteFormProcessing ? 'Eliminando...' : 'Eliminar Acceso' }}
                                </button>
                                <button type="button" @click="showDeleteModal = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white hover:bg-gray-50 text-sm font-bold text-gray-700 transition sm:mt-0 sm:w-auto">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
