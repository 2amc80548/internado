<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const props = defineProps<{
    estudiantes: any[];
    cursos: any[];
    cursosBth: any[];
    provincias: any[];
    comunidades: any[];
    gestiones: any[];
}>();

const showModal = ref(false);
const isEditing = ref(false);
const isReadOnlyMode = ref(true);
const subiendoFoto = ref(false);
const showPromotionModal = ref(false);
const showViewModal = ref(false);
const selectedEstudiante = ref<any>(null);
const showNotesModal = ref(false);
const studentForNotes = ref<any>(null);
const selectedGestionIdForNotes = ref<number | string>('');
const subiendoBoletinAdmin = ref(false);
const subiendoBoletinPeriodo = ref<number | null>(null);
const filterCursoWizard = ref('');

// Filtros avanzados
const filter = ref({
    texto: '',
    comunidad_id: '',
    carrera_tecnica_id: '',
    curso_id: '',
    estado_global: '',
    sexo: '',
    pabellon: ''
});

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
    año_egreso_bachiller: '' as number | string
});

// Comunidades dependientes de provincia (en el formulario)
const comunidadesForm = computed(() => {
    if (!form.provincia_id) return [];
    const prov = props.provincias.find(p => p.id === form.provincia_id);
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

const countHombres = computed(() => {
    return filteredEstudiantes.value.filter(e => e.persona?.sexo === 'M').length;
});

const countMujeres = computed(() => {
    return filteredEstudiantes.value.filter(e => e.persona?.sexo === 'F').length;
});

const openCreateModal = () => {
    isEditing.value = false;
    isReadOnlyMode.value = false;
    form.reset();
    showModal.value = true;
};

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

const habilitarModificacion = () => {
    if (confirm('¿Seguro que desea modificar los datos de este estudiante?')) {
        isReadOnlyMode.value = false;
    }
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
        form.put(route('estudiantes.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('estudiantes.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

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

const sortedRegistrations = (est: any) => {
    if (!est) return [];
    const regs = est.registrosInternado || est.registros_internado || [];
    return [...regs].sort((a, b) => {
        const yearA = parseInt(a.gestion?.nombre_gestion || '0');
        const yearB = parseInt(b.gestion?.nombre_gestion || '0');
        return yearB - yearA;
    });
};

const exportToExcel = () => {
    const list = filteredEstudiantes.value;
    if (list.length === 0) {
        alert('No hay estudiantes para exportar con los filtros actuales.');
        return;
    }

    const headers = [
        'C.I.',
        'Nombre Completo',
        'Celular Estudiante',
        'Género',
        'F. Nacimiento',
        'Comunidad',
        'Grado/Curso Actual',
        'Especialidad BTH',
        'Pabellón',
        'Cama',
        'Tutor Nombre',
        'Tutor Celular',
        'Estado Global',
        'Año Egreso Bachiller',
        'Año Egreso BTH'
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

const openViewModal = (est: any) => {
    selectedEstudiante.value = est;
    showViewModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedEstudiante.value = null;
};

const selectedRegForNotes = computed(() => {
    if (!studentForNotes.value) return null;
    const regs = studentForNotes.value.registrosInternado || studentForNotes.value.registros_internado || [];
    if (selectedGestionIdForNotes.value === '') {
        return regs[0] || null;
    }
    return regs.find((r: any) => String(r.gestion_id) === String(selectedGestionIdForNotes.value)) || regs[0] || null;
});

const availableRegistrationsForNotes = computed(() => {
    if (!studentForNotes.value) return [];
    const regs = studentForNotes.value.registrosInternado || studentForNotes.value.registros_internado || [];
    
    const sorted = [...regs].sort((a: any, b: any) => {
        const yearA = parseInt(a.gestion?.nombre_gestion || '0');
        const yearB = parseInt(b.gestion?.nombre_gestion || '0');
        return yearB - yearA;
    });

    const filtered = sorted.filter((reg: any) => reg.estado_anual === 'Aprobado' || reg.estado_anual === 'Cursando');
    const seen = new Set();
    return filtered.filter((reg: any) => {
        if (seen.has(reg.gestion_id)) {
            return false;
        }
        seen.add(reg.gestion_id);
        return true;
    });
});

const openNotesModal = (est: any) => {
    studentForNotes.value = est;
    const available = availableRegistrationsForNotes.value;
    if (available.length > 0) {
        selectedGestionIdForNotes.value = available[0].gestion_id;
    } else {
        selectedGestionIdForNotes.value = '';
    }
    showNotesModal.value = true;
};

const closeNotesModal = () => {
    showNotesModal.value = false;
    studentForNotes.value = null;
    selectedGestionIdForNotes.value = '';
};

const adminUploadBoletin = (e: Event, period: number) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0] && selectedRegForNotes.value) {
        const file = target.files[0];
        
        if (file.size > 5 * 1024 * 1024) {
            alert('El archivo no debe superar los 5MB');
            target.value = '';
            return;
        }
        
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            alert('Solo se permiten archivos PDF, JPG o PNG');
            target.value = '';
            return;
        }
        
        subiendoBoletinAdmin.value = true;
        subiendoBoletinPeriodo.value = period;
        
        const fileFormData = new FormData();
        fileFormData.append('registro_internado_id', String(selectedRegForNotes.value.id));
        fileFormData.append('numero_periodo', String(period));
        fileFormData.append('ruta_archivo', file);
        
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
    }
};

const adminDeleteBoletin = (boletinId: number) => {
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

const adminSubirFoto = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0] && form.id) {
        const file = target.files[0];
        
        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen no debe superar los 2MB');
            return;
        }

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
    }
};

const activeGestion = computed(() => {
    return props.gestiones ? props.gestiones.find((g: any) => g.estado === 'Activo') : null;
});

const activeGestionName = computed(() => {
    return activeGestion.value ? activeGestion.value.nombre_gestion : 'No Detectada';
});

const selectedSourceGestionId = ref<number | string>('');
const showOfertarModal = ref(false);

const sourceGestiones = computed(() => {
    if (!props.gestiones || !activeGestion.value) return [];
    return props.gestiones.filter((g: any) => g.id !== activeGestion.value.id);
});

// Encontrar la gestión inmediatamente anterior a la gestión activa actual
const immediatePreviousGestion = computed(() => {
    if (!props.gestiones || !activeGestion.value) return null;
    const activeYear = parseInt(activeGestion.value.nombre_gestion);
    if (isNaN(activeYear)) return null;
    
    const olderGestiones = props.gestiones.filter((g: any) => {
        const y = parseInt(g.nombre_gestion);
        return !isNaN(y) && y < activeYear;
    });
    
    if (olderGestiones.length === 0) return null;
    
    // Retornar la gestión más reciente entre las más antiguas
    return olderGestiones.sort((a: any, b: any) => parseInt(b.nombre_gestion) - parseInt(a.nombre_gestion))[0];
});

// Edición Inline de Fechas
const editActiveDatesMode = ref(false);
const activeDatesForm = useForm({
    fecha_inicio: '',
    fecha_fin: '',
});

const triggerEditActiveDates = () => {
    if (activeGestion.value) {
        activeDatesForm.fecha_inicio = activeGestion.value.fecha_inicio;
        activeDatesForm.fecha_fin = activeGestion.value.fecha_fin;
        editActiveDatesMode.value = true;
    }
};

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

const offeredGestiones = computed(() => {
    return props.gestiones ? props.gestiones.filter((g: any) => g.estado === 'Inactivo') : [];
});

const studentsInSourceGestion = computed(() => {
    if (!selectedSourceGestionId.value) return [];
    return props.estudiantes.filter(est => {
        const regs = est.registrosInternado || est.registros_internado || [];
        return regs.some((r: any) => String(r.gestion_id) === String(selectedSourceGestionId.value));
    });
});

const ofertarForm = useForm({
    nombre_gestion: '',
    fecha_inicio: '',
    fecha_fin: '',
    tipo_periodo_academico: 'Trimestre',
    cantidad_periodos: 3
});

const submitOferta = () => {
    ofertarForm.post(route('gestiones.ofertar'), {
        onSuccess: () => {
            showOfertarModal.value = false;
            ofertarForm.reset();
            alert('Nueva gestión académica ofertada e iniciada como ACTIVA exitosamente.');
        }
    });
};

const promotionForm = useForm({
    promociones: [] as any[]
});

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
            selected: !regDest && !isGraduated && !isRetirado, // Checked by default if not promoted/graduated/retired yet
            motivo_retiro: est.motivo_retiro || ''
        };
    });
};

const openPromotionWizard = () => {
    filterCursoWizard.value = '';
    editActiveDatesMode.value = false;
    
    // Seleccionar la gestión inmediatamente anterior si existe
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

const handleStatusChange = (index: number) => {
    const row = promotionForm.promociones[index];
    const est = props.estudiantes.find(e => e.id === row.estudiante_id);
    const regs = est?.registrosInternado || est?.registros_internado || [];
    const regSource = regs.find((r: any) => String(r.gestion_id) === String(selectedSourceGestionId.value));
    
    if (row.estado_anual === 'Reprobado') {
        row.curso_id = regSource?.curso_id || '';
    } else if (row.estado_anual === 'Retirado') {
        row.curso_id = '';
        row.curso_bth_id = '';
    } else if (row.estado_anual === 'Aprobado') {
        const currentCursoId = regSource?.curso_id || '';
        let nextCursoId = currentCursoId;
        if (currentCursoId) {
            const curIdx = props.cursos.findIndex(c => String(c.id) === String(currentCursoId));
            if (curIdx !== -1 && curIdx + 1 < props.cursos.length) {
                nextCursoId = props.cursos[curIdx + 1].id;
            }
        }
        row.curso_id = nextCursoId;
    }
};

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

    // Validar motivo de retiro
    const missingMotivo = selectedPromoList.find(p => p.estado_anual === 'Retirado' && !p.motivo_retiro);
    if (missingMotivo) {
        alert(`Por favor especifique el Motivo de Retiro para ${missingMotivo.nombre_completo}.`);
        return;
    }

    const courseLabel = filterCursoWizard.value ? `del curso "${filterCursoWizard.value}"` : 'seleccionados';

    if (confirm(`¿Está seguro de confirmar y promocionar los ${selectedPromoList.length} estudiantes ${courseLabel}? Los estudiantes se inscribirán en el periodo activo (${activeGestionName.value}).`)) {
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

// Modal de Anulación Segura
const showAnularModal = ref(false);
const anularForm = useForm({
    estudiante_id: null as number | null,
    nombre_completo: '',
    password: '',
});

const triggerAnularEstudiante = (est: any) => {
    anularForm.reset();
    anularForm.clearErrors();
    anularForm.estudiante_id = est.id;
    anularForm.nombre_completo = `${est.persona?.nombre} ${est.persona?.ap_paterno} ${est.persona?.ap_materno || ''}`;
    showAnularModal.value = true;
};

const submitAnular = () => {
    if (!anularForm.password) {
        alert('Debe ingresar su contraseña de confirmación.');
        return;
    }

    anularForm.delete(route('estudiantes.destroy', anularForm.estudiante_id), {
        onSuccess: () => {
            showAnularModal.value = false;
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
                
                <!-- Barra de Super Filtros -->
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
                                <input v-model="filter.texto" type="text" placeholder="CI, Nombre, Cel..." class="w-full pl-8 pr-3 py-1.5 border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
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
                    

                </div>

                <!-- Tabla de Resultados -->
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estudiante</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ubicación y Cama</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tutor Apoderado</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Curso / Especialidad</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-4 class='text-right' text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="est in filteredEstudiantes" :key="est.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img v-if="est.ruta_foto" :src="est.ruta_foto" class="h-10 w-10 rounded-full object-cover">
                                                <div v-else class="h-10 w-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-800 font-bold">
                                                    {{ est.persona?.nombre?.charAt(0) }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-gray-900">{{ est.persona?.nombre }} {{ est.persona?.ap_paterno }} {{ est.persona?.ap_materno || '' }}</div>
                                                <div class="text-xs text-gray-500">CI: {{ est.persona?.ci }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium">{{ getLatestReg(est)?.pabellon || 'Sin Pabellón' }}</div>
                                        <div class="text-xs text-gray-500">Cama: {{ getLatestReg(est)?.cama || 'S/N' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <template v-if="est.tutor?.persona">
                                            <div class="text-xs text-gray-900 font-semibold">{{ est.tutor.persona.nombre }} {{ est.tutor.persona.ap_paterno }}</div>
                                            <div class="text-[10px] text-gray-500 mt-0.5">Cel: {{ est.tutor.persona.celular || 'S/N' }}</div>
                                        </template>
                                        <span v-else class="text-xs text-gray-400">Sin Tutor</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-xs text-gray-800 font-bold">Curso: {{ getLatestReg(est)?.curso?.nombre || 'Ninguno' }}</div>
                                        <div class="text-[10px] text-indigo-600 font-semibold mt-1 max-w-[200px] truncate" :title="getLatestReg(est)?.curso_bth?.carrera_tecnica?.nombre">
                                            BTH: {{ getLatestReg(est)?.curso_bth?.carrera_tecnica?.nombre || 'Ninguno' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="{
                                            'bg-green-100 text-green-800 border-green-200': est.estado_global === 'Activo',
                                            'bg-gray-100 text-gray-800 border-gray-200': est.estado_global === 'Retirado',
                                            'bg-blue-100 text-blue-800 border-blue-200': est.estado_global === 'Bachiller',
                                            'bg-indigo-100 text-indigo-800 border-indigo-200': est.estado_global === 'Graduado BTH',
                                            'bg-red-100 text-red-800 border-red-200': est.estado_global === 'Anulado'
                                        }" class="px-2.5 py-0.5 inline-flex text-[10px] font-black rounded-full border">
                                            {{ est.estado_global || 'Activo' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex justify-end items-center gap-2">
                                        <!-- Ficha Detallada -->
                                        <button @click="openViewModal(est)" class="text-amber-600 hover:text-amber-900 bg-amber-50 p-2 rounded-lg" title="Ficha Completa">
                                            🔍
                                        </button>
                                        <!-- Cargar Boletines -->
                                        <button @click="openNotesModal(est)" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg" title="Boletines (Notas)">
                                            📚
                                        </button>
                                        <!-- Editar Ficha -->
                                        <button @click="openEditModal(est)" class="text-teal-600 hover:text-teal-900 bg-teal-50 p-2 rounded-lg" title="Editar Ficha">
                                            ✏️
                                        </button>
                                        <!-- Eliminar Ficha -->
                                        <button @click="triggerAnularEstudiante(est)" class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg font-bold text-xs" title="Eliminar Estudiante Permanentemente">
                                            🗑️ Eliminar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredEstudiantes.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 font-bold text-sm">
                                        No se encontraron registros de estudiantes con los filtros actuales.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- MODAL AGREGAR / EDITAR ESTUDIANTE -->
                <div v-if="showModal" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="closeModal"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                            <form @submit.prevent="submitForm">
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[80vh] overflow-y-auto">
                                    <h3 class="text-xl leading-6 font-bold text-gray-900 mb-6 border-b pb-4">
                                        {{ isEditing ? 'Editar Ficha de Estudiante' : 'Nueva Ficha de Estudiante' }}
                                    </h3>

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

                                    <fieldset :disabled="isReadOnlyMode" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        
                                        <!-- FOTO (Solo Edición) -->
                                        <div v-if="isEditing" class="md:col-span-2 bg-slate-50 p-4 rounded-xl border border-slate-200 mb-2 flex flex-col sm:flex-row items-center gap-4">
                                            <div class="w-24 h-24 bg-gray-100 border border-gray-300 rounded-full overflow-hidden flex items-center justify-center shrink-0 shadow-inner relative">
                                                <img v-if="form.ruta_foto" :src="form.ruta_foto" class="w-full h-full object-cover">
                                                <span v-else class="text-2xl font-bold text-gray-400">👤</span>
                                            </div>
                                            <div class="flex-1 text-center sm:text-left">
                                                <h4 class="font-bold text-gray-800 text-sm mb-1">Fotografía del Alumno</h4>
                                                <p class="text-xs text-gray-500 mb-3">Recomendable fondo claro. Formato JPG o PNG, máximo 2MB.</p>
                                                <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                                                    <label class="cursor-pointer bg-teal-600 hover:bg-teal-700 text-white font-bold py-1.5 px-3 rounded-lg text-xs transition shadow-sm">
                                                        <span>📷 Subir Nueva Foto</span>
                                                        <input type="file" class="hidden" accept="image/*" @change="adminSubirFoto">
                                                    </label>
                                                    <span v-if="subiendoFoto" class="text-xs text-teal-600 font-bold self-center animate-pulse">Subiendo...</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- DATOS PERSONALES -->
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

                                        <!-- DATOS DE INTERNADO -->
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

                                        <!-- DATOS ACADEMICOS -->
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

                                        <!-- TUTOR -->
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
                                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse rounded-b-2xl border-t border-slate-100">
                                    <button v-if="!isReadOnlyMode" type="submit" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-teal-600 text-sm font-bold text-white hover:bg-teal-700 sm:ml-3 sm:w-auto transition">
                                        Guardar Estudiante
                                    </button>
                                    <button v-if="isEditing && isReadOnlyMode" @click.prevent="habilitarModificacion" type="button" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto transition">
                                        🔒 Modificar Ficha
                                    </button>
                                    <button @click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-200 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 sm:mt-0 sm:ml-3 sm:w-auto transition">
                                        Cerrar / Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- MODAL FICHA DETALLADA / VISTA COMPLETA -->
                <div v-if="showViewModal && selectedEstudiante" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="closeViewModal"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border">
                            <div class="bg-white p-6 relative">
                                <div class="flex items-start justify-between border-b pb-4 mb-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900">Ficha Técnica e Historial del Estudiante</h3>
                                        <p class="text-xs text-gray-400">CI: {{ selectedEstudiante.persona?.ci }} &bull; Fecha Registro: {{ new Date(selectedEstudiante.created_at).toLocaleDateString() }}</p>
                                    </div>
                                    <button @click="closeViewModal" class="text-gray-400 hover:text-gray-700">✕</button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <!-- Photo Column -->
                                    <div class="md:col-span-1 flex flex-col items-center border-r md:pr-6 border-gray-100">
                                        <div class="w-32 h-44 bg-gray-100 rounded-xl overflow-hidden border border-gray-200 flex items-center justify-center shadow-md">
                                            <img v-if="selectedEstudiante.ruta_foto" :src="selectedEstudiante.ruta_foto" class="w-full h-full object-cover">
                                            <span v-else class="text-5xl">👤</span>
                                        </div>
                                        <span class="mt-4 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-teal-50 text-teal-700 border border-teal-200">
                                            {{ selectedEstudiante.estado_global || 'Activo' }}
                                        </span>
                                    </div>

                                    <!-- Details Columns -->
                                    <div class="md:col-span-2 space-y-4 text-sm">
                                        <div>
                                            <h4 class="font-bold text-gray-400 text-xs uppercase tracking-wider">Estudiante</h4>
                                            <p class="text-base font-bold text-slate-800">{{ selectedEstudiante.persona?.nombre }} {{ selectedEstudiante.persona?.ap_paterno }} {{ selectedEstudiante.persona?.ap_materno }}</p>
                                            <p class="text-xs text-gray-500">Género: {{ selectedEstudiante.persona?.sexo === 'M' ? 'Masculino' : 'Femenino' }} &bull; Celular: {{ selectedEstudiante.persona?.celular || 'S/N' }}</p>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <h4 class="font-bold text-gray-400 text-xs uppercase tracking-wider">Ubicación Habitación</h4>
                                                <p class="font-semibold text-gray-800">{{ getLatestReg(selectedEstudiante)?.pabellon || 'S/N' }}</p>
                                                <p class="text-xs text-gray-500">Cama: {{ getLatestReg(selectedEstudiante)?.cama || 'S/N' }}</p>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-gray-400 text-xs uppercase tracking-wider">Procedencia</h4>
                                                <p class="font-semibold text-gray-800">{{ selectedEstudiante.comunidad?.nombre || 'S/N' }}</p>
                                                <p class="text-xs text-gray-500">Provincia: {{ selectedEstudiante.comunidad?.provincia?.nombre || 'S/N' }}</p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <h4 class="font-bold text-gray-400 text-xs uppercase tracking-wider">Curso Regular Activo</h4>
                                                <p class="font-semibold text-gray-800">{{ getLatestReg(selectedEstudiante)?.curso?.nombre || 'Ninguno' }}</p>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-gray-400 text-xs uppercase tracking-wider">Carrera BTH</h4>
                                                <p class="font-semibold text-gray-800 text-xs truncate">{{ getLatestReg(selectedEstudiante)?.curso_bth?.carrera_tecnica?.nombre || 'Ninguna Especialidad' }}</p>
                                            </div>
                                        </div>

                                        <div v-if="selectedEstudiante.tutor?.persona" class="p-3 bg-amber-50/50 rounded-xl border border-amber-100/50">
                                            <h4 class="font-bold text-amber-800 text-xs uppercase tracking-wider">Apoderado / Tutor</h4>
                                            <p class="font-bold text-gray-800 text-xs mt-1">{{ selectedEstudiante.tutor.persona.nombre }} {{ selectedEstudiante.tutor.persona.ap_paterno }} (Cel: {{ selectedEstudiante.tutor.persona.celular || 'S/N' }})</p>
                                        </div>

                                        <!-- Motivo de Retiro / Anulación -->
                                        <div v-if="selectedEstudiante.estado_global === 'Retirado' && selectedEstudiante.motivo_retiro" class="p-3 bg-rose-50 rounded-xl border border-rose-100 text-xs">
                                            <h4 class="font-bold text-rose-800 text-[10px] uppercase tracking-wider">Motivo de Retiro</h4>
                                            <p class="text-rose-900 font-bold mt-1">{{ selectedEstudiante.motivo_retiro }}</p>
                                        </div>

                                        <div v-if="selectedEstudiante.estado_global === 'Anulado' && selectedEstudiante.motivo_anulacion" class="p-3 bg-slate-100 rounded-xl border border-slate-200 text-xs">
                                            <h4 class="font-bold text-slate-800 text-[10px] uppercase tracking-wider">Motivo de Anulación</h4>
                                            <p class="text-slate-900 font-bold mt-1">{{ selectedEstudiante.motivo_anulacion }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 border-t pt-4">
                                    <h4 class="font-bold text-gray-800 text-xs uppercase tracking-wider mb-3">📜 Trayectoria y Registro de Gestión</h4>
                                    <div class="space-y-2 max-h-40 overflow-y-auto">
                                        <div v-for="reg in sortedRegistrations(selectedEstudiante)" :key="reg.id" class="p-2.5 bg-gray-50 border rounded-xl text-xs flex justify-between items-center">
                                            <div>
                                                <span class="font-bold text-teal-700 bg-teal-50 border px-1.5 py-0.5 rounded mr-2">Gestión {{ reg.gestion?.nombre_gestion }}</span>
                                                <strong class="text-gray-800">{{ reg.curso?.nombre || 'S/N' }}</strong>
                                                <span class="text-gray-400 mx-1">&bull;</span>
                                                <span class="text-gray-500">BTH: {{ reg.curso_bth?.carrera_tecnica?.nombre || 'Ninguno' }}</span>
                                            </div>
                                            <span class="font-black uppercase tracking-wider text-[10px] text-green-800 bg-green-100 border px-2 py-0.5 rounded-full">
                                                {{ reg.estado_anual }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-6 py-4 flex justify-between rounded-b-2xl border-t">
                                <button @click="handleEditFromView" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-5 rounded-xl text-xs shadow-md transition">
                                    Editar Ficha
                                </button>
                                <button @click="closeViewModal" class="bg-white hover:bg-gray-50 text-slate-700 border font-bold py-2 px-5 rounded-xl text-xs shadow-sm transition">
                                    Cerrar Ficha
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL DE GESTION DE BOLETINES (NOTAS) -->
                <div v-if="showNotesModal && studentForNotes" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="closeNotesModal"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border">
                            <div class="bg-white p-6 relative">
                                <div class="flex items-center justify-between border-b pb-4 mb-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900">Boletines Escolares</h3>
                                        <p class="text-xs text-gray-500">Estudiante: {{ studentForNotes.persona?.nombre }} {{ studentForNotes.persona?.ap_paterno }}</p>
                                    </div>
                                    <button @click="closeNotesModal" class="text-gray-400 hover:text-gray-700">✕</button>
                                </div>

                                <div v-if="availableRegistrationsForNotes.length === 0" class="py-8 text-center text-gray-400 font-bold text-sm">
                                    El estudiante no cuenta con inscripciones activas o cursadas registradas.
                                </div>
                                <div v-else class="space-y-6">
                                    <div class="flex items-center gap-2">
                                        <label class="text-xs font-bold text-gray-500 uppercase">Seleccionar Gestión Escolar:</label>
                                        <select v-model="selectedGestionIdForNotes" class="border-gray-300 rounded-lg text-xs shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                            <option v-for="reg in availableRegistrationsForNotes" :key="reg.id" :value="reg.gestion_id">
                                                Gestión {{ reg.gestion?.nombre_gestion }}
                                            </option>
                                        </select>
                                    </div>

                                    <div v-if="selectedRegForNotes" class="bg-slate-50 p-4 border border-slate-100 rounded-xl space-y-4">
                                        <div class="flex justify-between items-center text-xs text-gray-600 font-bold uppercase tracking-wider">
                                            <span>Detalle Periodos Académicos</span>
                                            <span class="text-teal-700">Dividido en: {{ selectedRegForNotes.gestion?.cantidad_periodos || 3 }} {{ selectedRegForNotes.gestion?.tipo_periodo_academico }}s</span>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 mt-2">
                                            <div v-for="period in (selectedRegForNotes.gestion?.cantidad_periodos || 3)" :key="period" class="bg-white p-3 rounded-xl border border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-3">
                                                <span class="text-sm font-bold text-gray-700">{{ selectedRegForNotes.gestion?.tipo_periodo_academico }} {{ period }}</span>
                                                
                                                <div class="flex items-center gap-2">
                                                    <!-- Si ya tiene boletín -->
                                                    <template v-if="selectedRegForNotes.boletines?.find((b: any) => b.numero_periodo === period)">
                                                        <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 text-[10px] font-black uppercase">Boletín Subido</span>
                                                        <a :href="`/storage/${selectedRegForNotes.boletines.find((b: any) => b.numero_periodo === period).ruta_archivo}`" target="_blank" class="text-xs underline hover:text-indigo-800 font-bold">Ver notas</a>
                                                        <button @click="adminDeleteBoletin(selectedRegForNotes.boletines.find((b: any) => b.numero_periodo === period).id)" class="text-red-500 hover:text-red-700 text-xs font-bold bg-red-50 p-1.5 rounded-lg">✕ Eliminar</button>
                                                    </template>
                                                    <!-- Si no tiene boletín -->
                                                    <template v-else>
                                                        <label class="cursor-pointer bg-teal-50 hover:bg-teal-100 text-teal-700 font-black py-1 px-3.5 rounded-lg text-xs transition border border-teal-100 inline-block text-center shadow-xs">
                                                            <span>📤 Cargar Notas (PDF/JPG)</span>
                                                            <input type="file" class="hidden" accept=".pdf,image/*" @change="adminUploadBoletin($event, period)">
                                                        </label>
                                                        <span v-if="subiendoBoletinAdmin && subiendoBoletinPeriodo === period" class="text-xs text-teal-600 font-bold animate-pulse">Subiendo...</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-6 py-4 flex justify-end rounded-b-2xl border-t">
                                <button @click="closeNotesModal" class="bg-white hover:bg-gray-50 text-slate-700 border font-bold py-2 px-5 rounded-xl text-xs shadow-sm transition">
                                    Cerrar Boletines
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL DE ASISTENTE DE PROMOCION Y CIERRE DE GESTION -->
                <div v-if="showPromotionModal" class="fixed z-50 inset-0 overflow-y-auto animate-fade-in">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="showPromotionModal = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full border border-slate-100">
                            
                            <div class="bg-white p-6 max-h-[85vh] overflow-y-auto space-y-6">
                                <div class="flex items-center justify-between border-b pb-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-slate-800">Cierre de Gestión Académica y Promoción Escolar</h3>
                                        <p class="text-xs text-gray-500">Este asistente creará una nueva gestión académica e inscribirá de forma masiva a los estudiantes cursando activos.</p>
                                    </div>
                                    <button @click="showPromotionModal = false" class="text-gray-400 hover:text-gray-700">✕</button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-slate-50 p-5 border border-slate-200 rounded-2xl">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-black text-slate-500 uppercase">1. Seleccionar Gestión Escolar de Origen (Anterior)</label>
                                        <div class="flex gap-2 mt-1.5">
                                            <select v-model="selectedSourceGestionId" @change="updatePromotionWizardList" class="block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500 font-bold text-teal-700 py-2">
                                                <option value="" disabled>Seleccione la gestión anterior...</option>
                                                <option v-for="g in sourceGestiones" :key="g.id" :value="g.id" :disabled="g.id !== immediatePreviousGestion?.id">
                                                    Gestión {{ g.nombre_gestion }} ({{ g.fecha_inicio }} al {{ g.fecha_fin }}) {{ g.id !== immediatePreviousGestion?.id ? ' (Inhabilitada)' : ' (Origen Recomendado)' }}
                                                </option>
                                            </select>
                                            <button @click="showOfertarModal = true" type="button" class="bg-teal-600 hover:bg-teal-700 text-white font-bold px-4 py-2 rounded-lg text-xs transition duration-200 shrink-0 shadow-sm flex items-center gap-1.5 animate-pulse">
                                                <span>📢</span> Ofertar Gestión
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-black text-slate-500 uppercase">2. Gestión Destino (Activa Actual)</label>
                                        <div class="mt-2">
                                            <div class="flex flex-col gap-1.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-3 py-1.5 rounded-lg text-sm font-black bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wide">
                                                        {{ activeGestionName }} (Activa)
                                                    </span>
                                                    <button v-if="activeGestion && !editActiveDatesMode" @click="triggerEditActiveDates" type="button" class="text-xs font-black text-indigo-600 hover:text-indigo-800 transition flex items-center gap-1" title="Editar Fechas">
                                                        ✏️ Editar Fechas
                                                    </button>
                                                </div>
                                                
                                                <!-- Visualizar fechas actuales -->
                                                <div v-if="activeGestion && !editActiveDatesMode" class="text-xs text-gray-500 font-semibold mt-0.5">
                                                    📅 {{ activeGestion.fecha_inicio }} al {{ activeGestion.fecha_fin }}
                                                </div>

                                                <!-- Formulario inline para modificar fechas -->
                                                <div v-if="editActiveDatesMode" class="bg-indigo-50/50 p-2.5 rounded-xl border border-indigo-100 space-y-2 mt-1 w-full max-w-[280px]">
                                                    <div class="space-y-1">
                                                        <label class="block text-[9px] font-bold text-gray-500 uppercase">Fecha Inicio</label>
                                                        <input v-model="activeDatesForm.fecha_inicio" type="date" required class="border-gray-300 rounded-lg text-xs py-0.5 px-2 w-full">
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="block text-[9px] font-bold text-gray-500 uppercase">Fecha Fin</label>
                                                        <input v-model="activeDatesForm.fecha_fin" type="date" required class="border-gray-300 rounded-lg text-xs py-0.5 px-2 w-full">
                                                    </div>
                                                    <div class="flex gap-2 justify-end pt-1">
                                                        <button @click="editActiveDatesMode = false" type="button" class="bg-white border rounded px-2 py-0.5 text-[9px] font-bold text-gray-600 hover:bg-gray-50">Cancelar</button>
                                                        <button @click="submitActiveDatesUpdate" type="button" :disabled="activeDatesForm.processing" class="bg-indigo-600 text-white rounded px-2 py-0.5 text-[9px] font-bold hover:bg-indigo-700">
                                                            {{ activeDatesForm.processing ? 'Guardando...' : 'Guardar' }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50 p-4 rounded-xl border border-slate-100">
                                        <div>
                                            <h4 class="font-black text-slate-700 text-xs uppercase tracking-wider block">Planilla de Promoción de Alumnos</h4>
                                            <p class="text-[10px] text-gray-500 mt-0.5">Selecciona con checkbox los alumnos de la gestión anterior y promociónalos.</p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <div class="flex items-center gap-2">
                                                <label class="text-xs font-bold text-gray-500">Filtrar Curso:</label>
                                                <select v-model="filterCursoWizard" class="border-gray-300 rounded-lg text-xs shadow-sm focus:ring-teal-500 focus:border-teal-500 py-1 px-2 font-bold text-teal-700 bg-white">
                                                    <option value="">Todos los cursos</option>
                                                    <option v-for="c in cursos" :key="c.id" :value="c.nombre">{{ c.nombre }}</option>
                                                </select>
                                            </div>
                                            <!-- Numerito / Contador pedido por el usuario -->
                                            <span class="px-2.5 py-1 rounded-md text-xs font-black bg-indigo-100 text-indigo-800 border border-indigo-200">
                                                Alumnos Marcados: {{ promotionForm.promociones.filter(p => p.selected && !p.alreadyPromoted).length }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="border rounded-2xl overflow-hidden max-h-60 overflow-y-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50 sticky top-0 z-10">
                                                <tr>
                                                    <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase w-12">Promover</th>
                                                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Estudiante</th>
                                                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Curso Anterior</th>
                                                    <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase">Estado Académico</th>
                                                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Curso Siguiente / Motivo</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-100 text-sm text-slate-600">
                                                <tr v-for="(p, index) in promotionForm.promociones" :key="p.estudiante_id" v-show="!filterCursoWizard || p.curso_actual_nombre === filterCursoWizard" class="hover:bg-gray-50/50">
                                                    <td class="px-4 py-2 text-center">
                                                        <span v-if="p.isGraduated" class="text-indigo-600 font-black text-[10px] bg-indigo-50 border px-1.5 py-0.5 rounded">🎓 Egresado</span>
                                                        <span v-else-if="p.isRetirado" class="text-rose-600 font-black text-[10px] bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded">❌ Retirado</span>
                                                        <span v-else-if="p.alreadyPromoted" class="text-emerald-600 font-bold text-xs">✔️ Cursando</span>
                                                        <input v-else type="checkbox" v-model="p.selected" class="rounded text-teal-600 focus:ring-teal-500 w-4 h-4 cursor-pointer">
                                                    </td>
                                                    <td class="px-4 py-2 font-semibold" :class="{'text-gray-400': p.alreadyPromoted, 'text-slate-800': !p.alreadyPromoted}">
                                                        {{ p.nombre_completo }} <span class="text-gray-400 font-medium text-xs">({{ p.ci }})</span>
                                                    </td>
                                                    <td class="px-4 py-2 font-bold text-teal-700" :class="{'opacity-50': p.alreadyPromoted}">{{ p.curso_actual_nombre }}</td>
                                                    <td class="px-4 py-2 text-center">
                                                        <span v-if="p.isGraduated" class="text-xs font-black text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded">Aprobado</span>
                                                        <span v-else-if="p.isRetirado" class="text-xs font-black text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded">Retirado</span>
                                                        <span v-else-if="p.alreadyPromoted" class="text-xs font-bold text-indigo-700 bg-indigo-50 border px-2 py-0.5 rounded">{{ p.estado_anual }}</span>
                                                        <select v-else v-model="p.estado_anual" @change="handleStatusChange(index)" class="border-gray-300 rounded-lg text-xs py-1 focus:ring-teal-500 focus:border-teal-500 bg-slate-50 font-bold">
                                                            <option value="Aprobado">Aprobado</option>
                                                            <option value="Reprobado">Reprobado</option>
                                                            <option value="Retirado">Sin Inscripción (Retirado)</option>
                                                        </select>
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <span v-if="p.isGraduated" class="text-[10px] font-black text-indigo-800 bg-indigo-100 px-2 py-0.5 rounded flex items-center gap-1 w-max">
                                                            🎓 Egresa Bachiller
                                                        </span>
                                                        <span v-else-if="p.isRetirado" class="text-[10px] font-black text-rose-800 bg-rose-100 px-2 py-0.5 rounded block max-w-[200px] truncate" :title="p.motivo_retiro">
                                                            Retirado: {{ p.motivo_retiro || 'Sin motivo' }}
                                                        </span>
                                                        <span v-else-if="p.alreadyPromoted" class="text-[10px] font-black text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded">Ya Promocionado</span>
                                                        <template v-else>
                                                            <div v-if="p.estado_anual === 'Retirado'" class="space-y-1">
                                                                <input type="text" v-model="p.motivo_retiro" placeholder="Especificar Motivo de Retiro *" class="border-red-300 rounded-lg text-xs py-1 px-2 w-full bg-red-50 text-red-900 font-medium" required>
                                                            </div>
                                                            <span v-else-if="(p.curso_actual_nombre.includes('6to') || p.curso_actual_nombre.includes('Sexto')) && p.estado_anual === 'Aprobado'" class="text-xs px-2.5 py-1 rounded bg-indigo-100 text-indigo-800 border border-indigo-200 flex items-center gap-1 w-max font-black animate-pulse">
                                                                🎓 Egresa Bachiller
                                                            </span>
                                                            <select v-else v-model="p.curso_id" :disabled="p.estado_anual === 'Retirado'" class="border-gray-300 rounded-lg text-xs py-1 focus:ring-teal-500 focus:border-teal-500 max-w-[200px] font-black bg-white">
                                                                <option value="">Ninguno</option>
                                                                <option v-for="c in cursos" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                                                            </select>
                                                        </template>
                                                    </td>
                                                </tr>
                                                <tr v-if="promotionForm.promociones.length === 0">
                                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400 font-bold text-xs">
                                                        No hay estudiantes registrados en la gestión de origen seleccionada.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse rounded-b-3xl border-t border-slate-100">
                                <button @click="submitPromotion" :disabled="promotionForm.processing" type="button" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-gradient-to-r from-teal-600 to-teal-700 text-sm font-bold text-white hover:from-teal-700 hover:to-teal-800 sm:ml-3 sm:w-auto transition transform hover:scale-102">
                                    {{ promotionForm.processing ? 'Procesando Promociones...' : 'Confirmar y Promocionar' }}
                                </button>
                                <button @click="showPromotionModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-200 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 sm:mt-0 sm:ml-3 sm:w-auto transition">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL SECUNDARIO: OFERTAR GESTION ACADEMICA -->
                <div v-if="showOfertarModal" class="fixed z-[60] inset-0 overflow-y-auto animate-fade-in">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/60" @click="showOfertarModal = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100">
                            
                            <form @submit.prevent="submitOferta">
                                <div class="bg-white p-6 space-y-4">
                                    <h3 class="text-lg font-bold text-slate-800 border-b pb-3 flex items-center gap-2">
                                        <span>📢</span> Ofertar Nueva Gestión Escolar
                                    </h3>

                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Nombre de la Gestión (Año)</label>
                                            <input v-model="ofertarForm.nombre_gestion" type="text" required placeholder="Ej. 2027" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500 font-bold text-teal-700">
                                            <p v-if="ofertarForm.errors.nombre_gestion" class="text-rose-500 text-xs mt-1">{{ ofertarForm.errors.nombre_gestion }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Fecha de Inicio de Actividades</label>
                                            <input v-model="ofertarForm.fecha_inicio" type="date" required class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Fecha de Finalización Prevista</label>
                                            <input v-model="ofertarForm.fecha_fin" type="date" required class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Distribución de Periodos</label>
                                            <select v-model="ofertarForm.cantidad_periodos" @change="ofertarForm.tipo_periodo_academico = ofertarForm.cantidad_periodos === 4 ? 'Bimestre' : (ofertarForm.cantidad_periodos === 3 ? 'Trimestre' : 'Semestre')" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                                <option :value="4">4 Bimestres</option>
                                                <option :value="3">3 Trimestres</option>
                                                <option :value="2">2 Semestres</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse border-t gap-2">
                                    <button type="submit" :disabled="ofertarForm.processing" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-sm font-bold text-white transition sm:ml-3 sm:w-auto">
                                        {{ ofertarForm.processing ? 'Registrando...' : 'Ofertar Gestión' }}
                                    </button>
                                    <button type="button" @click="showOfertarModal = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto transition">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- MODAL: ELIMINAR ESTUDIANTE SEGURO -->
                <div v-if="showAnularModal" class="fixed z-[60] inset-0 overflow-y-auto animate-fade-in">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/60" @click="showAnularModal = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-red-100">
                            <form @submit.prevent="submitAnular">
                                <div class="bg-red-50/50 p-6 border-b border-red-100">
                                    <h3 class="text-lg font-bold text-red-800 flex items-center gap-2">
                                        <span>🗑️</span> Eliminación Permanente de Estudiante
                                    </h3>
                                    <p class="text-xs text-red-600 mt-1 font-semibold">
                                        Esta acción es irreversible. Se eliminará la ficha, historial de notas, mensualidades y cuenta del estudiante de forma definitiva.
                                    </p>
                                </div>
                                <div class="bg-white p-6 space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wide">Estudiante a Eliminar</label>
                                        <p class="text-sm font-bold text-slate-800 mt-1">{{ anularForm.nombre_completo }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Contraseña de Administrador <span class="text-red-500">*</span></label>
                                        <input v-model="anularForm.password" type="password" required placeholder="Ingrese su contraseña actual para confirmar" class="w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-red-500 focus:border-red-500">
                                        <p v-if="anularForm.errors.anular_password" class="text-rose-500 text-xs mt-1">{{ anularForm.errors.anular_password }}</p>
                                    </div>
                                </div>
                                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse border-t gap-2">
                                    <button type="submit" :disabled="anularForm.processing" class="w-full inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-red-600 hover:bg-red-700 text-sm font-bold text-white transition sm:ml-3 sm:w-auto">
                                        {{ anularForm.processing ? 'Eliminando...' : 'Confirmar Eliminación' }}
                                    </button>
                                    <button type="button" @click="showAnularModal = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto transition">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
