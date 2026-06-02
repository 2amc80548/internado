<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Importación de los componentes modulares hijos
import UserTable from './Partials/UserTable.vue';
import UserModal from './Partials/UserModal.vue';
import PasswordModal from './Partials/PasswordModal.vue';
import DeleteConfirmModal from './Partials/DeleteConfirmModal.vue';

// Propiedades recibidas desde el servidor a través de Inertia
const props = defineProps<{
    users: any[];
    roles: any[];
}>();

// Estados reactivos de control para ventanas modales y vistas
const showModal = ref(false);
const isEditing = ref(false);
const isReadOnlyMode = ref(true);
const showPasswordModal = ref(false);
const userForPassword = ref<any>(null);
const showViewModal = ref(false);
const selectedUser = ref<any>(null);

// Filtros reactivos de búsqueda avanzada en el panel superior
const filter = ref({
    texto: '',
    rol_id: '',
    estado_cuenta: '',
    sexo: ''
});

// Interruptor para la carga completa optimizada de la base de datos
const cargarTodosSwitch = ref(false);

// Función que realiza la petición HTTP con los filtros activos al servidor
const performServerSearch = () => {
    router.get(route('users.index'), {
        search: filter.value.texto,
        rol_id: filter.value.rol_id,
        estado_cuenta: filter.value.estado_cuenta,
        sexo: filter.value.sexo,
        cargar_todos: cargarTodosSwitch.value ? '1' : '0'
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['users']
    });
};

// Observadores para reaccionar al cambio de filtros y recargar los datos
watch(cargarTodosSwitch, () => {
    performServerSearch();
});

watch(() => [
    filter.value.rol_id,
    filter.value.estado_cuenta,
    filter.value.sexo
], () => {
    performServerSearch();
});

// Hook inicial para parsear parámetros de la URL al cargar la página
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('cargar_todos') === '1') {
        cargarTodosSwitch.value = true;
    }
    if (urlParams.get('search')) {
        filter.value.texto = urlParams.get('search') || '';
    }
});

// Colección de IDs seleccionados para acciones masivas (Aprobación)
const selectedUsers = ref<number[]>([]);

// Formulario de Inertia para la creación y edición (CRUD) de usuarios
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

// Listado de usuarios procesado reactivamente
const filteredUsers = computed(() => {
    return props.users;
});

// Métricas informativas de la vista (Cuentas de hombres, mujeres y pendientes)
const countHombres = computed(() => {
    return props.users.filter(u => u.persona?.sexo === 'M').length;
});

const countMujeres = computed(() => {
    return props.users.filter(u => u.persona?.sexo === 'F').length;
});

const countPendientes = computed(() => {
    return props.users.filter(u => u.estado_cuenta === 'Pendiente').length;
});

// Abre el modal para registrar un nuevo usuario administrativo
const openCreateModal = () => {
    isEditing.value = false;
    isReadOnlyMode.value = false;
    form.reset();
    showModal.value = true;
};

// Abre el modal para editar datos existentes de una cuenta
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

// Cierra el modal CRUD y limpia errores
const closeModal = () => {
    showModal.value = false;
    form.reset();
};

// Procesa el guardado del formulario (Creación o Actualización) con confirmación previa
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

// Exporta la lista filtrada de usuarios a formato CSV compatible con Excel
const exportToExcel = () => {
    const list = props.users;
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

// Abre el modal visor con la ficha técnica detallada del usuario
const openViewModal = (user: any) => {
    selectedUser.value = user;
    showViewModal.value = true;
};

// Cierra el visor de detalles del usuario
const closeViewModal = () => {
    showViewModal.value = false;
    selectedUser.value = null;
};

// Estados reactivos de control para el modal de eliminación con contraseña
const showDeleteModal = ref(false);
const userIdToDelete = ref<number | null>(null);
const deletePassword = ref('');
const deleteError = ref('');
const deleteFormProcessing = ref(false);

// Abre el modal para confirmar la eliminación de un usuario ingresando contraseña
const confirmDeleteUser = (id: number) => {
    userIdToDelete.value = id;
    deletePassword.value = '';
    deleteError.value = '';
    showDeleteModal.value = true;
};

// Procesa el envío seguro para la eliminación física del usuario
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

// Formulario reactivo para la actualización de contraseñas de cuentas
const formPassword = useForm({
    password: '',
    password_confirmation: ''
});

// Abre el modal para restablecer la clave de seguridad del usuario
const openPasswordModal = (user: any) => {
    userForPassword.value = user;
    formPassword.reset();
    showPasswordModal.value = true;
};

// Envía la solicitud de restablecimiento de contraseña al servidor
const submitPassword = () => {
    formPassword.post(route('users.password', userForPassword.value.id), {
        onSuccess: () => {
            showPasswordModal.value = false;
            alert('Contraseña actualizada exitosamente.');
        }
    });
};

// Registra la inhabilitación/rechazo de un usuario
const rechazarUsuario = (id: number) => {
    if (confirm('¿Estás seguro de que deseas rechazar la solicitud de acceso de este usuario?')) {
        router.put(route('users.rechazar', id));
    }
};

// Aprueba formalmente la cuenta de forma individual
const aprobarCuentaIndividual = (id: number) => {
    if (confirm('¿Estás seguro de aprobar la cuenta de este usuario?')) {
        router.put(route('users.aprobar', id));
    }
};

// Procesa la aprobación de múltiples cuentas seleccionadas simultáneamente
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
                
                <!-- Barra de Super Filtros y Búsquedas -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-800">Filtros y Búsqueda</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-slate-100 text-slate-800 border border-slate-200 uppercase tracking-wide">
                                    Filtrados: {{ props.users.length }}
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
                                <input v-model="filter.texto" @keydown.enter="performServerSearch" type="text" placeholder="CI, Nombre, Celular... (Enter)" class="w-full pl-8 pr-3 py-2 border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
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

                    <!-- Asistente de rendimiento (Toggle de Carga Completa) -->
                    <div class="mt-5 pt-4 border-t border-gray-150 flex items-center justify-between gap-4 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-500 animate-pulse" v-if="props.users.length === 0"></span>
                            <span class="text-xs text-gray-500 font-bold">
                                {{ props.users.length === 0 ? 'Carga optimizada: listado oculto inicialmente para acelerar el sistema.' : 'Listado dinámico cargado exitosamente.' }}
                            </span>
                        </div>
                        <label class="inline-flex items-center gap-2.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 px-4 py-2 rounded-xl cursor-pointer transition select-none">
                            <input type="checkbox" v-model="cargarTodosSwitch" class="h-4.5 w-4.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 cursor-pointer">
                            <span class="text-xs font-black text-gray-700">Cargar listado completo de usuarios</span>
                        </label>
                    </div>

                </div>

                <!-- Tabla de Resultados Principal (Modularizada) -->
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                    <div v-if="props.users.length === 0" class="py-20 px-8 text-center max-w-lg mx-auto">
                        <div class="h-16 w-16 rounded-full bg-teal-50 text-teal-600 flex items-center justify-center mx-auto mb-5 text-3xl shadow-sm">
                            👤
                        </div>
                        <h3 class="text-lg font-black text-gray-800 mb-1.5">Listado de Usuarios</h3>
                        <p class="text-sm text-gray-500 leading-relaxed mb-6">Para garantizar la máxima velocidad de carga, la lista no se carga por defecto. Active el interruptor de carga completa, realice una búsqueda y presione Enter o aplique un filtro para visualizar los registros.</p>
                        <button @click="cargarTodosSwitch = true" class="inline-flex items-center justify-center bg-teal-600 hover:bg-teal-700 text-white font-black py-2.5 px-6 rounded-xl shadow-md transition text-xs">
                            Cargar Todo el Listado
                        </button>
                    </div>
                    <div v-else>
                        <UserTable 
                            :users="users"
                            :filtered-users="filteredUsers"
                            v-model:selected-users="selectedUsers"
                            @view="openViewModal"
                            @approve="aprobarCuentaIndividual"
                            @reject="rechazarUsuario"
                            @password="openPasswordModal"
                            @edit="openEditModal"
                            @delete="confirmDeleteUser"
                        />
                    </div>
                </div>

                <!-- Modal CRUD de Usuario (Modularizado) -->
                <UserModal 
                    :show-modal="showModal"
                    :is-editing="isEditing"
                    :roles="roles"
                    :form="form"
                    @close="closeModal"
                    @submit="submitForm"
                />

                <!-- Modal Ver Detalle / Ficha Técnica Resumen -->
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

                <!-- Modal Cambiar Contraseña (Modularizado) -->
                <PasswordModal 
                    :show-password-modal="showPasswordModal"
                    :user-for-password="userForPassword"
                    :form-password="formPassword"
                    @close="showPasswordModal = false"
                    @submit="submitPassword"
                />

                <!-- Modal Eliminar Cuenta con Contraseña de Seguridad (Modularizado) -->
                <DeleteConfirmModal 
                    :show-delete-modal="showDeleteModal"
                    v-model:delete-password="deletePassword"
                    :delete-error="deleteError"
                    :delete-form-processing="deleteFormProcessing"
                    @close="showDeleteModal = false"
                    @submit="submitDeleteUser"
                />

            </div>
        </div>
    </AuthenticatedLayout>
</template>
