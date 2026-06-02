<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CameraCropModal from '@/Components/CameraCropModal.vue';
import axios from 'axios';

// Importación de componentes parciales refactorizados
import EstudiantesTable from './Partials/EstudiantesTable.vue';
import EstudianteModal from './Partials/EstudianteModal.vue';
import EstudianteFichaModal from './Partials/EstudianteFichaModal.vue';
import NotesHistoryModal from './Partials/NotesHistoryModal.vue';
import PromotionWizardModal from './Partials/PromotionWizardModal.vue';
import AnularModal from './Partials/AnularModal.vue';

// Definición de las propiedades recibidas desde el controlador Laravel
const props = defineProps<{
    estudiantes: any[];
    cursos: any[];
    cursosBth: any[];
    provincias: any[];
    comunidades: any[];
    gestiones: any[];
}>();

// Estados reactivos globales de la vista
const showModal = ref(false);
const isEditing = ref(false);
const isReadOnlyMode = ref(true);
const subiendoFoto = ref(false);
const showPromotionModal = ref(false);
const showViewModal = ref(false);
const selectedEstudiante = ref<any>(null);
const showNotesModal = ref(false);
const studentForNotes = ref<any>(null);
const subiendoBoletinAdmin = ref(false);
const subiendoBoletinPeriodo = ref<number | null>(null);
const showCameraCropModal = ref(false);
const cargarTodosSwitch = ref(false);

// Filtros avanzados del listado principal
const filter = ref({
    texto: '',
    comunidad_id: '',
    carrera_tecnica_id: '',
    curso_id: '',
    estado_global: '',
    sexo: '',
    pabellon: ''
});

// Instancia de useForm para el formulario de Agregar/Editar Estudiante
const form = useForm({
    id: null as number | null,
    ruta_foto: null as string | null,
    ci: '',
    nombre: '',
    ap_paterno: '',
    ap_materno: '',
    celular: '',
    sexo: '',
    fecha_nacimiento: '',
    
    // Campos para estudiante
    provincia_id: '',
    comunidad_id: '',
    curso_id: '',
    curso_bth_id: '',
    año_egreso_bth: '' as number | string,
    pabellon: '',
    cama: '',
    
    // Campos para tutor
    tutor_ci: '',
    tutor_nombre: '',
    tutor_ap_paterno: '',
    tutor_ap_materno: '',
    tutor_celular: '',
    
    // Historial
    estado_global: 'Activo',
    año_egreso_bachiller: '' as number | string,
    motivo_retiro: ''
});

// Instancia de useForm para actualizar fechas de la gestión activa
const activeDatesForm = useForm({
    fecha_inicio: '',
    fecha_fin: '',
});

// Instancia de useForm para ofertar una nueva gestión escolar
const ofertarForm = useForm({
    nombre_gestion: '',
    fecha_inicio: '',
    fecha_fin: '',
    tipo_periodo_academico: 'Trimestre',
    cantidad_periodos: 3
});

// Instancia de useForm para la confirmación de promociones en lote
const promotionForm = useForm({
    promociones: [] as any[]
});

// Instancia de useForm para confirmación de eliminación segura con contraseña
const anularForm = useForm({
    estudiante_id: null as number | null,
    nombre_completo: '',
    password: '',
});

// Estado de gestión anterior seleccionada
const selectedSourceGestionId = ref<number | string>('');
const editActiveDatesMode = ref(false);

// Búsqueda en servidor usando router de Inertia
const performServerSearch = () => {
    router.get(route('estudiantes.index'), {
        search: filter.value.texto,
        curso_id: filter.value.curso_id,
        comunidad_id: filter.value.comunidad_id,
        estado_global: filter.value.estado_global,
        sexo: filter.value.sexo,
        pabellon: filter.value.pabellon,
        cargar_todos: cargarTodosSwitch.value ? '1' : '0'
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['estudiantes']
    });
};

// Observadores de filtros y switch de optimización
watch(cargarTodosSwitch, () => {
    performServerSearch();
});

watch(() => [
    filter.value.comunidad_id,
    filter.value.curso_id,
    filter.value.estado_global,
    filter.value.sexo,
    filter.value.pabellon,
    filter.value.carrera_tecnica_id
], () => {
    performServerSearch();
});

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('cargar_todos') === '1') {
        cargarTodosSwitch.value = true;
    }
    if (urlParams.get('search')) {
        filter.value.texto = urlParams.get('search') || '';
    }
});

// Obtiene el registro de internado más reciente
const getLatestReg = (est: any) => {
    if (!est) return null;
    const regs = est.registrosInternado || est.registros_internado || [];
    if (regs.length === 0) return null;
    const sorted = [...regs].sort((a, b) => {
        const yearA = parseInt(a.gestion?.nombre_gestion || '0');
        const yearB = parseInt(b.gestion?.nombre_gestion || '0');
        if (yearA !== yearB) return yearB - yearA;
        return (b.id || 0) - (a.id || 0);
    });
    return sorted[0];
};

// Especialidades BTH basadas en la lista recibida
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

// Computa la lista de estudiantes filtrados localmente
const filteredEstudiantes = computed(() => {
    return props.estudiantes.filter(est => {
        let matchesText = true;
        if (filter.value.texto) {
            const q = filter.value.texto.toLowerCase();
            const nombre = `${est.persona?.nombre} ${est.persona?.ap_paterno}`.toLowerCase();
            const ci = est.persona?.ci?.toLowerCase() || '';
            const celularEst = est.persona?.celular?.toLowerCase() || '';
            const celularTut = est.tutor?.persona?.celular?.toLowerCase() || '';
            matchesText = nombre.includes(q) || ci.includes(q) || celularEst.includes(q) || celularTut.includes(q);
        }

        let matchesEstado = true;
        if (filter.value.estado_global !== '') {
            matchesEstado = est.estado_global === filter.value.estado_global;
        }
        
        let matchesComunidad = true;
        let matchesCurso = true;
        let matchesCarrera = true;
        let matchesSexo = true;
        let matchesPabellon = true;

        if (filter.value.comunidad_id) {
            matchesComunidad = String(est.comunidad_id) === String(filter.value.comunidad_id);
        }
        if (filter.value.curso_id) {
            const regs = est.registros_internado || est.registrosInternado || [];
            matchesCurso = regs.some((reg: any) => String(reg.curso_id) === String(filter.value.curso_id));
        }
        if (filter.value.carrera_tecnica_id) {
            const regs = est.registros_internado || est.registrosInternado || [];
            matchesCarrera = regs.some((reg: any) => {
                const bth = reg.curso_bth || reg.cursoBth;
                return bth?.carrera_tecnica_id && String(bth.carrera_tecnica_id) === String(filter.value.carrera_tecnica_id);
            });
        }
        if (filter.value.sexo) {
            matchesSexo = est.persona?.sexo === filter.value.sexo;
        }
        if (filter.value.pabellon) {
            const latestReg = getLatestReg(est);
            matchesPabellon = latestReg && latestReg.pabellon === filter.value.pabellon;
        }

        return matchesText && matchesComunidad && matchesCurso && matchesCarrera && matchesEstado && matchesSexo && matchesPabellon;
    });
});

// Cantidad de varones en el listado actual
const countHombres = computed(() => {
    return filteredEstudiantes.value.filter(e => e.persona?.sexo === 'M').length;
});

// Cantidad de damas en el listado actual
const countMujeres = computed(() => {
    return filteredEstudiantes.value.filter(e => e.persona?.sexo === 'F').length;
});

// Abre el modal para agregar un nuevo estudiante
const openCreateModal = () => {
    isEditing.value = false;
    isReadOnlyMode.value = false;
    form.reset();
    showModal.value = true;
};

// Abre el modal de edición cargando todos los datos del estudiante
const openEditModal = (est: any) => {
    isEditing.value = true;
    isReadOnlyMode.value = true;
    form.reset();
    form.clearErrors();
    
    form.id = est.id;
    form.ci = est.persona?.ci || '';
    form.nombre = est.persona?.nombre || '';
    form.ap_paterno = est.persona?.ap_paterno || '';
    form.ap_materno = est.persona?.ap_materno || '';
    form.celular = est.persona?.celular || '';
    form.sexo = est.persona?.sexo || '';
    form.fecha_nacimiento = est.persona?.fecha_nacimiento || '';
    
    form.ruta_foto = est.ruta_foto || null;
    form.comunidad_id = est.comunidad_id || '';
    form.provincia_id = est.comunidad?.provincia_id || '';
    form.año_egreso_bth = est.año_egreso_bth || '';
    form.estado_global = est.estado_global || 'Activo';
    form.año_egreso_bachiller = est.año_egreso_bachiller || '';
    form.motivo_retiro = est.motivo_retiro || '';

    const latestReg = getLatestReg(est);
    if (latestReg) {
        form.curso_id = latestReg.curso_id || '';
        form.curso_bth_id = latestReg.curso_bth_id || '';
        form.pabellon = latestReg.pabellon || '';
        form.cama = latestReg.cama || '';
    } else {
        form.curso_id = '';
        form.curso_bth_id = '';
        form.pabellon = '';
        form.cama = '';
    }

    if (est.tutor && est.tutor.persona) {
        form.tutor_ci = est.tutor.persona.ci || '';
        form.tutor_nombre = est.tutor.persona.nombre || '';
        form.tutor_ap_paterno = est.tutor.persona.ap_paterno || '';
        form.tutor_ap_materno = est.tutor.persona.ap_materno || '';
        form.tutor_celular = est.tutor.persona.celular || '';
    } else {
        form.tutor_ci = '';
        form.tutor_nombre = '';
        form.tutor_ap_paterno = '';
        form.tutor_ap_materno = '';
        form.tutor_celular = '';
    }

    showModal.value = true;
};

// Habilita la edición de campos en el modo lectura
const habilitarModificacion = () => {
    if (confirm('¿Seguro que desea modificar los datos de este estudiante?')) {
        isReadOnlyMode.value = false;
    }
};

// Cierra el modal de creación y edición
const closeModal = () => {
    showModal.value = false;
    form.reset();
};

// Envía el formulario para crear o actualizar el estudiante
const submitForm = () => {
    if (!confirm('¿Está seguro de que desea guardar los cambios?')) {
        return;
    }
    if (isEditing.value) {
        form.put(route('estudiantes.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('estudiantes.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

// Exportación a formato CSV compatible con Excel
const exportToExcel = () => {
    const list = filteredEstudiantes.value;
    if (list.length === 0) {
        alert('No hay estudiantes para exportar con los filtros actuales.');
        return;
    }

    const headers = [
        'C.I.', 'Nombre Completo', 'Celular Estudiante', 'Género', 'F. Nacimiento',
        'Comunidad', 'Grado/Curso Actual', 'Especialidad BTH', 'Pabellón', 'Cama',
        'Tutor Nombre', 'Tutor Celular', 'Estado Global', 'Año Egreso Bachiller', 'Año Egreso BTH'
    ];

    const rows = list.map(est => {
        const reg = getLatestReg(est);
        return [
            est.persona?.ci || '',
            `${est.persona?.nombre || ''} ${est.persona?.ap_paterno || ''} ${est.persona?.ap_materno || ''}`.trim(),
            est.persona?.celular || '',
            est.persona?.sexo === 'M' ? 'Masculino' : (est.persona?.sexo === 'F' ? 'Femenino' : 'No especificado'),
            est.persona?.fecha_nacimiento || '',
            est.comunidad?.nombre || '',
            reg?.curso?.nombre || '',
            reg?.curso_bth?.carrera_tecnica?.nombre || reg?.curso_bth?.carreraTecnica?.nombre || '',
            reg?.pabellon || '',
            reg?.cama || '',
            est.tutor?.persona ? `${est.tutor.persona.nombre} ${est.tutor.persona.ap_paterno}` : '',
            est.tutor?.persona?.celular || '',
            est.estado_global || 'Activo',
            est.año_egreso_bachiller || '',
            est.año_egreso_bth || ''
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
    link.setAttribute('download', `reporte_estudiantes_${new Date().toISOString().substring(0,10)}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Abre la ficha detallada
const openViewModal = (est: any) => {
    selectedEstudiante.value = est;
    showViewModal.value = true;
};

// Cierra la ficha detallada
const closeViewModal = () => {
    showViewModal.value = false;
    selectedEstudiante.value = null;
};

// Edición rápida de ficha desde el modal detallado
const handleEditFromView = () => {
    if (selectedEstudiante.value) {
        const est = selectedEstudiante.value;
        closeViewModal();
        openEditModal(est);
    }
};

// Abre el modal de notas y boletines
const openNotesModal = (est: any) => {
    studentForNotes.value = est;
    showNotesModal.value = true;
};

// Cierra el modal de notas y boletines
const closeNotesModal = () => {
    showNotesModal.value = false;
    studentForNotes.value = null;
};

// Lógica de subida asíncrona de boletines a través de Axios
const handleUploadBoletin = (payload: { file: File; period: number; registroId: number }) => {
    subiendoBoletinAdmin.value = true;
    subiendoBoletinPeriodo.value = payload.period;
    
    const fileFormData = new FormData();
    fileFormData.append('registro_internado_id', String(payload.registroId));
    fileFormData.append('numero_periodo', String(payload.period));
    fileFormData.append('ruta_archivo', payload.file);
    
    axios.post(route('boletines.store'), fileFormData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    }).then(response => {
        router.reload({
            only: ['estudiantes'],
            onSuccess: () => {
                subiendoBoletinAdmin.value = false;
                subiendoBoletinPeriodo.value = null;
                const updatedEst = props.estudiantes.find(e => e.id === studentForNotes.value.id);
                if (updatedEst) {
                    studentForNotes.value = updatedEst;
                }
                alert('Boletín subido exitosamente.');
            },
            onError: () => {
                subiendoBoletinAdmin.value = false;
                subiendoBoletinPeriodo.value = null;
            }
        });
    }).catch(error => {
        subiendoBoletinAdmin.value = false;
        subiendoBoletinPeriodo.value = null;
        alert('Ocurrió un error al subir el boletín.');
    });
};

// Lógica de eliminación de boletines académicos
const handleDeleteBoletin = (boletinId: number) => {
    if (confirm('¿Está seguro de eliminar este boletín? Esta acción no se puede deshacer.')) {
        axios.delete(route('boletines.destroy', boletinId))
            .then(() => {
                router.reload({
                    only: ['estudiantes'],
                    onSuccess: () => {
                        const updatedEst = props.estudiantes.find(e => e.id === studentForNotes.value.id);
                        if (updatedEst) {
                            studentForNotes.value = updatedEst;
                        }
                        alert('Boletín eliminado correctamente.');
                    }
                });
            })
            .catch(() => {
                alert('Error al intentar eliminar el boletín.');
            });
    }
};

// Guarda la fotografía recortada desde la cámara/recorte
const adminGuardarFotoRecortada = (file: File) => {
    if (!form.id) return;
    subiendoFoto.value = true;
    
    const fileFormData = new FormData();
    fileFormData.append('foto', file);

    axios.post(route('estudiantes.foto', form.id), fileFormData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    }).then(response => {
        form.ruta_foto = response.data.ruta_foto;
        const estIdx = props.estudiantes.findIndex(e => e.id === form.id);
        if (estIdx !== -1) {
            props.estudiantes[estIdx].ruta_foto = response.data.ruta_foto;
        }
        alert('Foto de perfil actualizada correctamente');
    }).catch(err => {
        console.error(err);
        alert('Error al subir la foto de perfil: ' + (err.response?.data?.message || err.message));
    }).finally(() => {
        subiendoFoto.value = false;
    });
};

// Obtiene la gestión escolar marcada como ACTIVA actualmente
const activeGestion = computed(() => {
    return props.gestiones ? props.gestiones.find((g: any) => g.estado === 'Activo') : null;
});

const activeGestionName = computed(() => {
    return activeGestion.value ? activeGestion.value.nombre_gestion : 'No Detectada';
});

// Filtra las gestiones para el selector de origen
const sourceGestiones = computed(() => {
    if (!props.gestiones || !activeGestion.value) return [];
    return props.gestiones.filter((g: any) => g.id !== activeGestion.value.id);
});

// Encuentra la gestión escolar inmediatamente anterior
const immediatePreviousGestion = computed(() => {
    if (!props.gestiones || !activeGestion.value) return null;
    const activeYear = parseInt(activeGestion.value.nombre_gestion);
    if (isNaN(activeYear)) return null;
    
    const olderGestiones = props.gestiones.filter((g: any) => {
        const y = parseInt(g.nombre_gestion);
        return !isNaN(y) && y < activeYear;
    });
    
    if (olderGestiones.length === 0) return null;
    return olderGestiones.sort((a: any, b: any) => parseInt(b.nombre_gestion) - parseInt(a.nombre_gestion))[0];
});

// Estudiantes registrados en la gestión de origen seleccionada
const studentsInSourceGestion = computed(() => {
    if (!selectedSourceGestionId.value) return [];
    return props.estudiantes.filter(est => {
        const regs = est.registrosInternado || est.registros_internado || [];
        return regs.some((r: any) => String(r.gestion_id) === String(selectedSourceGestionId.value));
    });
});

// Inicializa o actualiza la lista de estudiantes para el wizard de promoción
const updatePromotionWizardList = () => {
    promotionForm.reset();
    promotionForm.clearErrors();

    if (!selectedSourceGestionId.value || !activeGestion.value) {
        promotionForm.promociones = [];
        return;
    }

    promotionForm.promociones = studentsInSourceGestion.value.map(est => {
        const regs = est.registrosInternado || est.registros_internado || [];
        const regSource = regs.find((r: any) => String(r.gestion_id) === String(selectedSourceGestionId.value));
        const regDest = regs.find((r: any) => String(r.gestion_id) === String(activeGestion.value?.id));
        
        const currentCursoId = regSource?.curso_id || '';
        const currentCursoBthId = regSource?.curso_bth_id || '';
        
        let nextCursoId = currentCursoId;
        if (currentCursoId) {
            const curIdx = props.cursos.findIndex(c => String(c.id) === String(currentCursoId));
            if (curIdx !== -1 && curIdx + 1 < props.cursos.length) {
                nextCursoId = props.cursos[curIdx + 1].id;
            }
        }

        const isGraduated = est.estado_global === 'Bachiller' || est.estado_global === 'Graduado BTH';
        const isRetirado = est.estado_global === 'Retirado';

        return {
            estudiante_id: est.id,
            nombre_completo: `${est.persona?.nombre} ${est.persona?.ap_paterno} ${est.persona?.ap_materno || ''}`,
            ci: est.persona?.ci,
            curso_actual_nombre: regSource?.curso?.nombre || 'Ninguno',
            estado_anual: regDest ? regDest.estado_anual : (isRetirado ? 'Retirado' : 'Aprobado'),
            curso_id: regDest ? regDest.curso_id : nextCursoId,
            curso_bth_id: regDest ? regDest.curso_bth_id : currentCursoBthId,
            alreadyPromoted: !!regDest || isGraduated || isRetirado,
            isGraduated: isGraduated,
            isRetirado: isRetirado,
            selected: !regDest && !isGraduated && !isRetirado,
            motivo_retiro: est.motivo_retiro || ''
        };
    });
};

// Abre el modal de promociones
const openPromotionWizard = () => {
    editActiveDatesMode.value = false;
    
    if (immediatePreviousGestion.value) {
        selectedSourceGestionId.value = immediatePreviousGestion.value.id;
    } else if (sourceGestiones.value.length > 0) {
        selectedSourceGestionId.value = sourceGestiones.value[0].id;
    } else {
        selectedSourceGestionId.value = '';
    }
    
    updatePromotionWizardList();
    showPromotionModal.value = true;
};

// Cambia la gestión de origen en el wizard
const handleSourceGestionChange = (val: number | string) => {
    selectedSourceGestionId.value = val;
    updatePromotionWizardList();
};

// Abre el formulario inline para editar fechas de la gestión activa
const triggerEditActiveDates = () => {
    if (activeGestion.value) {
        activeDatesForm.fecha_inicio = activeGestion.value.fecha_inicio;
        activeDatesForm.fecha_fin = activeGestion.value.fecha_fin;
        editActiveDatesMode.value = true;
    }
};

// Actualiza las fechas de la gestión activa
const submitActiveDatesUpdate = () => {
    if (!activeGestion.value) return;

    activeDatesForm.put(route('gestiones.update', activeGestion.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editActiveDatesMode.value = false;
            alert('Fechas de gestión activa actualizadas exitosamente.');
        },
        onError: (err) => {
            console.error(err);
            alert('Ocurrió un error al actualizar las fechas.');
        }
    });
};

// Crea una nueva gestión académica
const submitOferta = (closeCallback: () => void) => {
    ofertarForm.post(route('gestiones.ofertar'), {
        onSuccess: () => {
            closeCallback();
            ofertarForm.reset();
            alert('Nueva gestión académica ofertada e iniciada como ACTIVA exitosamente.');
        }
    });
};

// Confirma y ejecuta el proceso masivo de promociones
const submitPromotion = () => {
    if (!activeGestion.value) {
        alert('Debe haber una gestión escolar activa.');
        return;
    }
    if (!selectedSourceGestionId.value) {
        alert('Debe seleccionar una gestión escolar de origen.');
        return;
    }

    const selectedPromoList = promotionForm.promociones.filter(p => p.selected && !p.alreadyPromoted);
    if (selectedPromoList.length === 0) {
        alert('Debe seleccionar al menos un estudiante con el checkbox para realizar la promoción.');
        return;
    }

    const missingMotivo = selectedPromoList.find(p => p.estado_anual.includes('Retirado') && !p.motivo_retiro);
    if (missingMotivo) {
        alert(`Por favor especifique el Motivo de Retiro para ${missingMotivo.nombre_completo}.`);
        return;
    }

    if (confirm(`¿Está seguro de confirmar y promocionar los ${selectedPromoList.length} estudiantes seleccionados? Los estudiantes se inscribirán en el periodo activo (${activeGestionName.value}).`)) {
        router.post(route('gestiones.promocionar'), {
            destino_gestion_id: activeGestion.value.id,
            origen_gestion_id: selectedSourceGestionId.value,
            promociones: selectedPromoList
        }, {
            onSuccess: () => {
                showPromotionModal.value = false;
                alert('Estudiantes promocionados e inscritos exitosamente en la gestión activa.');
            },
            onError: (err) => {
                console.error(err);
                alert('Ocurrió un error al realizar la promoción.');
            }
        });
    }
};

// Abre el modal para eliminación permanente/anulación
const triggerAnularEstudiante = (est: any) => {
    anularForm.reset();
    anularForm.clearErrors();
    anularForm.estudiante_id = est.id;
    anularForm.nombre_completo = `${est.persona?.nombre} ${est.persona?.ap_paterno} ${est.persona?.ap_materno || ''}`;
    anularForm.show = true; // Guardamos visibilidad directamente en el form o en ref secundaria
};

// Envía la solicitud de eliminación segura de estudiante
const submitAnular = () => {
    if (!anularForm.password) {
        alert('Debe ingresar su contraseña de confirmación.');
        return;
    }

    anularForm.delete(route('estudiantes.destroy', anularForm.estudiante_id), {
        onSuccess: () => {
            anularForm.show = false;
            alert('Estudiante y todos sus registros vinculados eliminados permanentemente.');
        },
        onError: (err) => {
            console.error(err);
        }
    });
};
</script>

<template>
    <Head title="Gestión de Estudiantes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                Gestión de Estudiantes
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Barra de Super Filtros y Acciones -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-800">Búsqueda y Filtros</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200 tracking-wide uppercase">
                                    Gestión Activa: {{ activeGestionName }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-slate-100 text-slate-800 border border-slate-200 uppercase tracking-wide">
                                    Cantidad: {{ filteredEstudiantes.length }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">Administra las fichas de los estudiantes registrados en el internado</p>
                        </div>
                        <div class="flex items-center gap-3 w-full md:w-auto justify-end flex-wrap">
                            <button @click="openPromotionWizard" class="bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white font-bold py-2 px-5 rounded-xl shadow-md transition text-sm flex items-center gap-1.5 transform hover:scale-102">
                                🎓 Promocionar Gestión Académica
                            </button>
                            <button @click="exportToExcel" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-5 rounded-xl shadow-md transition text-sm flex items-center gap-2 transform hover:scale-102">
                                📊 Descargar Excel
                            </button>
                            <button @click="openCreateModal" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded-xl shadow-sm transition text-sm">
                                + Nuevo Estudiante
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Buscar</label>
                            <div class="relative mt-1">
                                <input v-model="filter.texto" @keydown.enter="performServerSearch" type="text" placeholder="CI, Nombre... (Enter)" class="w-full pl-8 pr-3 py-1.5 border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Estado / Ficha</label>
                            <select v-model="filter.estado_global" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Todos los Estados</option>
                                <option value="Activo">Estudiante Activo</option>
                                <option value="Retirado">Retirado / Inactivo</option>
                                <option value="Bachiller">Egresado Bachiller</option>
                                <option value="Graduado BTH">Graduado BTH</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Género (Sexo)</label>
                            <select v-model="filter.sexo" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Todos</option>
                                <option value="M">Varón / Masculino</option>
                                <option value="F">Dama / Femenino</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Pabellón</label>
                            <select v-model="filter.pabellon" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Todos</option>
                                <option value="Pabellón Varones A">Pabellón Varones A</option>
                                <option value="Pabellón Varones B">Pabellón Varones B</option>
                                <option value="Pabellón Damas A">Pabellón Damas A</option>
                                <option value="Pabellón Damas B">Pabellón Damas B</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Comunidad</label>
                            <select v-model="filter.comunidad_id" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Todas</option>
                                <option v-for="com in comunidades" :key="com.id" :value="com.id">
                                    {{ com.nombre }} {{ com.provincia ? `(${com.provincia.nombre})` : '' }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Especialidad BTH</label>
                            <select v-model="filter.carrera_tecnica_id" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Todas</option>
                                <option v-for="carrera in carrerasBth" :key="carrera.id" :value="carrera.id">{{ carrera.nombre }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Curso</label>
                            <select v-model="filter.curso_id" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Todos</option>
                                <option v-for="curso in cursos" :key="curso.id" :value="curso.id">{{ curso.nombre }}</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Switch inline de optimización de velocidad de carga -->
                    <div class="mt-5 pt-4 border-t border-gray-150 flex items-center justify-between gap-4 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-500 animate-pulse" v-if="estudiantes.length === 0"></span>
                            <span class="text-xs text-gray-500 font-bold">
                                {{ estudiantes.length === 0 ? 'Carga optimizada: listado oculto inicialmente para acelerar el sistema.' : 'Listado dinámico cargado exitosamente.' }}
                            </span>
                        </div>
                        <label class="inline-flex items-center gap-2.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 px-4 py-2 rounded-xl cursor-pointer transition select-none">
                            <input type="checkbox" v-model="cargarTodosSwitch" class="h-4.5 w-4.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 cursor-pointer">
                            <span class="text-xs font-black text-gray-700">Cargar listado completo de estudiantes</span>
                        </label>
                    </div>

                </div>

                <!-- Tabla de Resultados Principal -->
                <EstudiantesTable
                    :estudiantes="filteredEstudiantes"
                    @view="openViewModal"
                    @notes="openNotesModal"
                    @edit="openEditModal"
                    @delete="triggerAnularEstudiante"
                    @load-all="cargarTodosSwitch = true"
                />

            </div>
        </div>

        <!-- MODAL AGREGAR / EDITAR ESTUDIANTE -->
        <EstudianteModal
            :show="showModal"
            :isEditing="isEditing"
            :isReadOnlyMode="isReadOnlyMode"
            :form="form"
            :provincias="provincias"
            :cursos="cursos"
            :cursosBth="cursosBth"
            :subiendoFoto="subiendoFoto"
            @close="closeModal"
            @submit="submitForm"
            @enable-edit="habilitarModificacion"
            @show-camera-crop="showCameraCropModal = true"
        />

        <!-- MODAL FICHA DETALLADA / VISTA COMPLETA -->
        <EstudianteFichaModal
            :show="showViewModal"
            :estudiante="selectedEstudiante"
            @close="closeViewModal"
            @edit="handleEditFromView"
        />

        <!-- MODAL DE GESTIÓN DE BOLETINES (NOTAS) -->
        <NotesHistoryModal
            :show="showNotesModal"
            :estudiante="studentForNotes"
            :subiendoBoletinAdmin="subiendoBoletinAdmin"
            :subiendoBoletinPeriodo="subiendoBoletinPeriodo"
            @close="closeNotesModal"
            @upload-boletin="handleUploadBoletin"
            @delete-boletin="handleDeleteBoletin"
        />

        <!-- MODAL DE ASISTENTE DE PROMOCIÓN Y CIERRE DE GESTIÓN -->
        <PromotionWizardModal
            :show="showPromotionModal"
            :gestiones="gestiones"
            :cursos="cursos"
            :estudiantes="estudiantes"
            :activeGestion="activeGestion"
            :activeGestionName="activeGestionName"
            :sourceGestiones="sourceGestiones"
            :immediatePreviousGestion="immediatePreviousGestion"
            :editActiveDatesMode="editActiveDatesMode"
            :activeDatesForm="activeDatesForm"
            :promotionForm="promotionForm"
            :ofertarForm="ofertarForm"
            :selectedSourceGestionId="selectedSourceGestionId"
            @close="showPromotionModal = false"
            @update-source-gestion="handleSourceGestionChange"
            @trigger-edit-dates="triggerEditActiveDates"
            @cancel-edit-dates="editActiveDatesMode = false"
            @submit-active-dates="submitActiveDatesUpdate"
            @submit-promotion="submitPromotion"
            @submit-oferta="submitOferta"
        />

        <!-- MODAL: ELIMINAR ESTUDIANTE SEGURO -->
        <AnularModal
            :show="anularForm.show"
            :form="anularForm"
            @close="anularForm.show = false"
            @submit="submitAnular"
        />

        <!-- Modal de Recorte y Cámara para la fotografía del perfil -->
        <CameraCropModal :show="showCameraCropModal" @close="showCameraCropModal = false" @cropped="adminGuardarFotoRecortada" />
    </AuthenticatedLayout>
</template>
