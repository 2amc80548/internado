<script setup lang="ts">
import { computed } from 'vue';

// Definición de las propiedades del componente
const props = defineProps<{
    show: boolean;
    isEditing: boolean;
    isReadOnlyMode: boolean;
    form: any;
    provincias: any[];
    cursos: any[];
    cursosBth: any[];
    subiendoFoto: boolean;
}>();

// Definición de los eventos emitidos por el componente
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
    (e: 'enable-edit'): void;
    (e: 'show-camera-crop'): void;
}>();

// Comunidades dependientes de provincia (filtradas para el formulario)
const comunidadesForm = computed(() => {
    if (!props.form.provincia_id) return [];
    const prov = props.provincias.find(p => p.id === props.form.provincia_id);
    return prov ? prov.comunidades : [];
});

// Especialidades BTH
const carrerasBth = computed(() => {
    const list: any[] = [];
    const ids = new Set();
    props.cursosBth.forEach(cb => {
        if (cb.carrera_tecnica && !ids.has(cb.carrera_tecnica.id)) {
            ids.add(cb.carrera_tecnica.id);
            list.push(cb.carrera_tecnica);
        }
    });
    return list;
});
</script>

<template>
    <!-- MODAL AGREGAR / EDITAR ESTUDIANTE -->
    <div v-if="show" class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Capa de desenfoque de fondo -->
            <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="emit('close')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Contenedor del contenido del modal -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <form @submit.prevent="emit('submit')">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[80vh] overflow-y-auto">
                        <h3 class="text-xl leading-6 font-bold text-gray-900 mb-6 border-b pb-4">
                            {{ isEditing ? 'Editar Ficha de Estudiante' : 'Nueva Ficha de Estudiante' }}
                        </h3>

                        <!-- Bloque de errores de validación -->
                        <div v-if="Object.keys(form.errors).length > 0" class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">⚠️</div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-semibold text-red-800">Hay errores de validación en el formulario:</h3>
                                    <ul class="mt-1 list-disc list-inside text-xs text-red-700">
                                        <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Campos editables o bloqueados por modo lectura -->
                        <fieldset :disabled="isReadOnlyMode" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- FOTOGRAFÍA (Solo Edición) -->
                            <div v-if="isEditing" class="md:col-span-2 bg-slate-50 p-4 rounded-xl border border-slate-200 mb-2 flex flex-col sm:flex-row items-center gap-4">
                                <div class="w-24 h-24 bg-gray-100 border border-gray-300 rounded-full overflow-hidden flex items-center justify-center shrink-0 shadow-inner relative">
                                    <img v-if="form.ruta_foto" :src="form.ruta_foto" class="w-full h-full object-cover">
                                    <span v-else class="text-2xl font-bold text-gray-400">👤</span>
                                </div>
                                <div class="flex-1 text-center sm:text-left">
                                    <h4 class="font-bold text-gray-800 text-sm mb-1">Fotografía del Alumno</h4>
                                    <p class="text-xs text-gray-500 mb-3">Recomendable fondo claro. Formato JPG o PNG, máximo 2MB.</p>
                                    <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                                        <button @click="emit('show-camera-crop')" type="button" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-1.5 px-3 rounded-lg text-xs transition shadow-sm flex items-center gap-1.5">
                                            📷 Actualizar Foto (Cámara / Archivo)
                                        </button>
                                        <span v-if="subiendoFoto" class="text-xs text-teal-600 font-bold self-center animate-pulse">Procesando...</span>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 1: DATOS PERSONALES -->
                            <div class="md:col-span-2">
                                <h4 class="font-bold text-teal-700 uppercase text-xs tracking-wider border-b pb-1">1. Datos Personales Básicos</h4>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Carnet de Identidad (CI)</label>
                                <input v-model="form.ci" :disabled="isEditing" type="text" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm py-2" required placeholder="CI del estudiante">
                                <p v-if="form.errors.ci" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.ci }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Nombres del Alumno</label>
                                <input v-model="form.nombre" type="text" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm py-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Apellido Paterno</label>
                                <input v-model="form.ap_paterno" type="text" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm py-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Apellido Materno</label>
                                <input v-model="form.ap_materno" type="text" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Celular del Alumno</label>
                                <input v-model="form.celular" type="text" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Fecha de Nacimiento</label>
                                <input v-model="form.fecha_nacimiento" type="date" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Sexo / Género</label>
                                <select v-model="form.sexo" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm py-2" required>
                                    <option value="">Seleccione...</option>
                                    <option value="M">Varón / Masculino</option>
                                    <option value="F">Dama / Femenino</option>
                                </select>
                            </div>

                            <!-- SECCIÓN 2: DATOS DE INTERNADO -->
                            <div class="md:col-span-2 mt-4">
                                <h4 class="font-bold text-teal-700 uppercase text-xs tracking-wider border-b pb-1">2. Ubicación e Internación</h4>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Provincia de Origen</label>
                                <select v-model="form.provincia_id" @change="form.comunidad_id = ''" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm py-2">
                                    <option value="">Seleccione Provincia...</option>
                                    <option v-for="prov in provincias" :key="prov.id" :value="prov.id">{{ prov.nombre }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Comunidad de Origen</label>
                                <select v-model="form.comunidad_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm py-2" :disabled="!form.provincia_id">
                                    <option value="">Seleccione Comunidad...</option>
                                    <option v-for="com in comunidadesForm" :key="com.id" :value="com.id">{{ com.nombre }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Pabellón de Dormitorio</label>
                                <select v-model="form.pabellon" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm py-2">
                                    <option value="">Ninguno</option>
                                    <option value="Pabellón Varones A">Pabellón Varones A</option>
                                    <option value="Pabellón Varones B">Pabellón Varones B</option>
                                    <option value="Pabellón Damas A">Pabellón Damas A</option>
                                    <option value="Pabellón Damas B">Pabellón Damas B</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Cama / Dormitorio</label>
                                <input v-model="form.cama" type="text" placeholder="Ej. 24" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm py-2">
                            </div>

                            <!-- SECCIÓN 3: DATOS ACADÉMICOS -->
                            <div class="md:col-span-2 mt-4">
                                <h4 class="font-bold text-teal-700 uppercase text-xs tracking-wider border-b pb-1">3. Cursos y Trayectoria</h4>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Grado/Curso Regular en Gestión Activa</label>
                                <select v-model="form.curso_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm py-2">
                                    <option value="">Ninguno</option>
                                    <option v-for="c in cursos" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Especialidad BTH (Nivel Técnico)</label>
                                <select v-model="form.curso_bth_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 text-sm py-2">
                                    <option value="">Ninguno</option>
                                    <option v-for="b in cursosBth" :key="b.id" :value="b.id">{{ b.carrera_tecnica?.nombre }} - {{ b.nivel }}</option>
                                </select>
                            </div>

                            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700">Estado de Ficha Estudiante</label>
                                    <select v-model="form.estado_global" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm sm:text-sm py-1.5 focus:ring-teal-500 focus:border-teal-500">
                                        <option value="Activo">Activo</option>
                                        <option value="Retirado">Retirado / Inactivo</option>
                                        <option value="Bachiller">Bachiller</option>
                                        <option value="Graduado BTH">Graduado BTH</option>
                                        <option value="Anulado" disabled>Anulado (Por Proceso Seguro)</option>
                                    </select>
                                </div>
                                <div v-if="form.estado_global === 'Bachiller' || form.estado_global === 'Graduado BTH'">
                                    <label class="block text-sm font-semibold text-gray-700">Año de Egreso Escolar</label>
                                    <input v-model="form.año_egreso_bachiller" type="number" placeholder="Ej. 2026" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm sm:text-sm py-1.5">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700">Año Egreso Especialidad BTH</label>
                                    <input v-model="form.año_egreso_bth" type="number" placeholder="Ej. 2026" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm sm:text-sm py-1.5">
                                </div>
                                <!-- Motivo de Retiro -->
                                <div v-if="form.estado_global === 'Retirado'" class="sm:col-span-3 mt-2">
                                    <label class="block text-xs font-bold text-rose-700 uppercase tracking-wide">Motivo de Retiro Escolar <span class="text-rose-500 font-bold">*</span></label>
                                    <textarea v-model="form.motivo_retiro" rows="2" placeholder="Describa de forma detallada el motivo por el cual el estudiante se retira del internado..." class="mt-1 block w-full border-rose-300 rounded-lg text-sm shadow-sm focus:ring-rose-500 focus:border-rose-500 bg-rose-50/20" required></textarea>
                                </div>
                            </div>

                            <!-- SECCIÓN 4: DATOS DEL TUTOR -->
                            <div class="md:col-span-2 mt-4 bg-amber-50/50 p-4 rounded-xl border border-amber-100">
                                <h4 class="font-bold text-amber-800 uppercase text-xs tracking-wider mb-4 border-b border-amber-100 pb-1">4. Datos del Apoderado / Tutor</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600">CI del Tutor</label>
                                        <input v-model="form.tutor_ci" type="text" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm py-1.5" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600">Celular del Tutor</label>
                                        <input v-model="form.tutor_celular" type="text" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm py-1.5">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600">Nombre del Tutor</label>
                                        <input v-model="form.tutor_nombre" type="text" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm py-1.5" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600">Apellido Paterno</label>
                                        <input v-model="form.tutor_ap_paterno" type="text" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm py-1.5" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600">Apellido Materno</label>
                                        <input v-model="form.tutor_ap_materno" type="text" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm py-1.5">
                                    </div>
                                </div>
                            </div>

                        </fieldset>
                    </div>
                    
                    <!-- Botones de Acción del Modal -->
                    <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse rounded-b-2xl border-t border-slate-100">
                        <button v-if="!isReadOnlyMode" type="submit" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-teal-600 text-sm font-bold text-white hover:bg-teal-700 sm:ml-3 sm:w-auto transition">
                            Guardar Estudiante
                        </button>
                        <button v-if="isEditing && isReadOnlyMode" @click.prevent="emit('enable-edit')" type="button" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto transition">
                            🔒 Modificar Ficha
                        </button>
                        <button @click="emit('close')" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-200 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 sm:mt-0 sm:ml-3 sm:w-auto transition">
                            Cerrar / Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
