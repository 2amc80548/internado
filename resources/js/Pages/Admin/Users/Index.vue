<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const props = defineProps<{
    users: any[];
    roles: any[];
    provincias: any[];
    comunidades: any[];
    cursos: any[];
    cursosBth: any[];
}>();

const showModal = ref(false);
const isEditing = ref(false);
const isReadOnlyMode = ref(true);
const showPasswordModal = ref(false);
const userForPassword = ref<any>(null);
const subiendoFoto = ref(false);
const showPromotionModal = ref(false);
const showViewModal = ref(false);
const selectedUser = ref<any>(null);
const showNotesModal = ref(false);
const studentForNotes = ref<any>(null);
const selectedGestionIdForNotes = ref<number | string>('');
const subiendoBoletinAdmin = ref(false);
const subiendoBoletinPeriodo = ref<number | null>(null);
const filterCursoWizard = ref('');

// Filtros avanzados
const filter = ref({
    texto: '',
    rol_id: '' as string | number,
    comunidad_id: '',
    carrera_tecnica_id: '',
    curso_id: '',
    estado_cuenta: '',
    sexo: '',
    pabellon: ''
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

onMounted(() => {
    const estudianteRol = props.roles.find(r => r.nombre === 'Estudiante');
    if (estudianteRol) {
        filter.value.rol_id = estudianteRol.id;
    }
});

const form = useForm({
    id: null as number | null,
    estudiante_id: null as number | null,
    ruta_foto: null as string | null,
    ci: '',
    nombre: '',
    ap_paterno: '',
    ap_materno: '',
    email: '',
    celular: '',
    sexo: '',
    rol_id: '' as number | string,
    estado_cuenta: '',
    
    // Campos para estudiante
    is_estudiante: false,
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

const isEstudianteRole = computed(() => {
    const selectedRole = props.roles.find(r => r.id === form.rol_id);
    const isEst = selectedRole && selectedRole.nombre === 'Estudiante';
    form.is_estudiante = isEst;
    return isEst;
});

// Comunidades dependientes de provincia (en el formulario)
const comunidadesForm = computed(() => {
    if (!form.provincia_id) return [];
    const prov = props.provincias.find(p => p.id === form.provincia_id);
    return prov ? prov.comunidades : [];
});

// Computed list of careers (from props.cursosBth)
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

const activeGestionName = computed(() => {
    for (const u of props.users) {
        const regs = u.persona?.estudiante?.registrosInternado || u.persona?.estudiante?.registros_internado || [];
        const activeReg = regs.find((r: any) => r.gestion?.estado === 'Activo');
        if (activeReg && activeReg.gestion) {
            return activeReg.gestion.nombre_gestion;
        }
    }
    return 'No Detectada';
});

const filteredUsers = computed(() => {
    return props.users.filter(user => {
        // Texto general (Nombre, CI, Celulares)
        let matchesText = true;
        if (filter.value.texto) {
            const q = filter.value.texto.toLowerCase();
            const nombre = `${user.persona?.nombre} ${user.persona?.ap_paterno}`.toLowerCase();
            const ci = user.persona?.ci.toLowerCase() || '';
            const celularEst = user.persona?.celular?.toLowerCase() || '';
            const celularTut = user.persona?.estudiante?.tutor?.persona?.celular?.toLowerCase() || '';
            matchesText = nombre.includes(q) || ci.includes(q) || celularEst.includes(q) || celularTut.includes(q);
        }

        let matchesRole = filter.value.rol_id === '' || String(user.rol_id) === String(filter.value.rol_id);
        
        let matchesEstado = true;
        if (filter.value.estado_cuenta !== '') {
            const val = filter.value.estado_cuenta;
            if (val === 'Pendiente') {
                matchesEstado = user.estado_cuenta === 'Pendiente';
            } else if (val === 'Rechazado') {
                matchesEstado = user.estado_cuenta === 'Rechazado';
            } else if (val === 'Aprobado') {
                matchesEstado = user.estado_cuenta === 'Aprobado';
            } else if (val === 'Activo') {
                matchesEstado = user.estado_cuenta === 'Aprobado' && (!user.persona?.estudiante || !user.persona.estudiante.estado_global || user.persona.estudiante.estado_global === 'Activo' || (user.persona.estudiante.estado_global === 'Graduado BTH' && !user.persona.estudiante.año_egreso_bachiller));
            } else if (val === 'Retirado') {
                matchesEstado = user.estado_cuenta === 'Aprobado' && user.persona?.estudiante?.estado_global === 'Retirado';
            } else if (val === 'Bachiller') {
                matchesEstado = user.estado_cuenta === 'Aprobado' && user.persona?.estudiante?.estado_global === 'Bachiller';
            } else if (val === 'Graduado BTH') {
                matchesEstado = user.estado_cuenta === 'Aprobado' && (user.persona?.estudiante?.estado_global === 'Graduado BTH' || !!user.persona?.estudiante?.año_egreso_bth);
            }
        }
        
        let matchesComunidad = true;
        let matchesCurso = true;
        let matchesCarrera = true;
        let matchesSexo = true;
        let matchesPabellon = true;

        if (filter.value.comunidad_id) {
            matchesComunidad = user.rol?.nombre === 'Estudiante' && String(user.persona?.estudiante?.comunidad_id) === String(filter.value.comunidad_id);
        }
        if (filter.value.curso_id) {
            const regs = user.persona?.estudiante?.registros_internado || user.persona?.estudiante?.registrosInternado || [];
            matchesCurso = user.rol?.nombre === 'Estudiante' && regs.some((reg: any) => String(reg.curso_id) === String(filter.value.curso_id));
        }
        if (filter.value.carrera_tecnica_id) {
            const regs = user.persona?.estudiante?.registros_internado || user.persona?.estudiante?.registrosInternado || [];
            matchesCarrera = user.rol?.nombre === 'Estudiante' && regs.some((reg: any) => {
                const bth = reg.curso_bth || reg.cursoBth;
                return bth?.carrera_tecnica_id && String(bth.carrera_tecnica_id) === String(filter.value.carrera_tecnica_id);
            });
        }
        if (filter.value.sexo) {
            matchesSexo = user.persona?.sexo === filter.value.sexo;
        }
        if (filter.value.pabellon) {
            const latestReg = getLatestReg(user);
            matchesPabellon = user.rol?.nombre === 'Estudiante' && latestReg && latestReg.pabellon === filter.value.pabellon;
        }

        return matchesText && matchesRole && matchesComunidad && matchesCurso && matchesCarrera && matchesEstado && matchesSexo && matchesPabellon;
    });
});

const countHombres = computed(() => {
    return filteredUsers.value.filter(u => u.persona?.sexo === 'M').length;
});

const countMujeres = computed(() => {
    return filteredUsers.value.filter(u => u.persona?.sexo === 'F').length;
});

const openCreateModal = () => {
    isEditing.value = false;
    isReadOnlyMode.value = false;
    form.reset();
    form.is_estudiante = false;
    showModal.value = true;
};

const openEditModal = (user: any) => {
    isEditing.value = true;
    isReadOnlyMode.value = true; // Empieza en modo lectura al ver detalles del estudiante
    form.reset();
    form.clearErrors(); // Limpiar errores previos
    
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
    
    const isEst = user.rol?.nombre === 'Estudiante';
    form.is_estudiante = isEst;

    if (isEst && user.persona?.estudiante) {
        const est = user.persona.estudiante;
        form.estudiante_id = est.id;
        form.ruta_foto = est.ruta_foto || null;
        form.comunidad_id = est.comunidad_id || '';
        form.provincia_id = est.comunidad?.provincia_id || '';
        form.año_egreso_bth = est.año_egreso_bth || '';

        const latestReg = getLatestReg(user);
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

        form.estado_global = est.estado_global || 'Activo';
        form.año_egreso_bachiller = est.año_egreso_bachiller || '';
    } else {
        // Limpiar campos de estudiante para no estudiantes
        form.comunidad_id = '';
        form.provincia_id = '';
        form.año_egreso_bth = '';
        form.curso_id = '';
        form.curso_bth_id = '';
        form.pabellon = '';
        form.cama = '';
        form.tutor_ci = '';
        form.tutor_nombre = '';
        form.tutor_ap_paterno = '';
        form.tutor_ap_materno = '';
        form.tutor_celular = '';
        form.estado_global = 'Activo';
        form.año_egreso_bachiller = '';
    }

    showModal.value = true;
};

const habilitarModificacion = () => {
    if (confirm('¿Seguro que desea modificar los datos de este usuario?')) {
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
        form.put(route('users.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const getLatestReg = (user: any) => {
    if (!user) return null;
    const regs = user.persona?.estudiante?.registrosInternado || user.persona?.estudiante?.registros_internado || [];
    if (regs.length === 0) return null;
    const sorted = [...regs].sort((a, b) => {
        const yearA = parseInt(a.gestion?.nombre_gestion || '0');
        const yearB = parseInt(b.gestion?.nombre_gestion || '0');
        if (yearA !== yearB) return yearB - yearA;
        return (b.id || 0) - (a.id || 0);
    });
    return sorted[0];
};

const sortedRegistrations = (user: any) => {
    if (!user) return [];
    const regs = user.persona?.estudiante?.registrosInternado || user.persona?.estudiante?.registros_internado || [];
    return [...regs].sort((a, b) => {
        const yearA = parseInt(a.gestion?.nombre_gestion || '0');
        const yearB = parseInt(b.gestion?.nombre_gestion || '0');
        return yearB - yearA; // Newest first
    });
};

const exportToExcel = () => {
    const list = filteredUsers.value;
    if (list.length === 0) {
        alert('No hay usuarios para exportar con los filtros actuales.');
        return;
    }

    // Cabeceras
    const headers = [
        'C.I.',
        'Nombre Completo',
        'Email',
        'Rol',
        'Celular',
        'Género',
        'Estado Cuenta',
        'Tipo Estudiante',
        'Comunidad',
        'Especialidad BTH',
        'Pabellón',
        'Cama',
        'Año Egreso Bachiller',
        'Año Egreso BTH'
    ];

    // Filas
    const rows = list.map(u => {
        const est = u.persona?.estudiante;
        const reg = getLatestReg(u);
        
        return [
            u.persona?.ci || '',
            `${u.persona?.nombre || ''} ${u.persona?.ap_paterno || ''} ${u.persona?.ap_materno || ''}`.trim(),
            u.email || '',
            u.rol?.nombre || '',
            u.persona?.celular || '',
            u.persona?.sexo === 'M' ? 'Masculino' : (u.persona?.sexo === 'F' ? 'Femenino' : 'No especificado'),
            u.estado_cuenta || '',
            est ? (est.estado_global || 'Activo') : 'N/A',
            est?.comunidad?.nombre || '',
            reg?.curso_bth?.carrera_tecnica?.nombre || reg?.curso_bth?.carreraTecnica?.nombre || '',
            reg?.pabellon || '',
            reg?.cama || '',
            est?.año_egreso_bachiller || '',
            est?.año_egreso_bth || ''
        ];
    });

    // Formatear como CSV compatible con Excel (separador punto y coma, con BOM UTF-8)
    const csvContent = [
        headers.join(';'),
        ...rows.map(row => row.map(val => {
            const strVal = String(val).replace(/;/g, ',').replace(/"/g, '""');
            return `"${strVal}"`;
        }).join(';'))
    ].join('\r\n');

    // Crear Blob con BOM para Excel UTF-8
    const BOM = '\uFEFF';
    const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
    
    // Descargar
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `reporte_usuarios_${new Date().toISOString().substring(0,10)}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const getProvinciaName = (comunidadId: any) => {
    if (!comunidadId) return '';
    const com = props.comunidades.find(c => String(c.id) === String(comunidadId));
    if (!com) return '';
    const prov = props.provincias.find(p => String(p.id) === String(com.provincia_id));
    return prov ? prov.nombre : '';
};

const openViewModal = (user: any) => {
    selectedUser.value = user;
    showViewModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedUser.value = null;
};

const selectedRegForNotes = computed(() => {
    if (!studentForNotes.value) return null;
    const regs = studentForNotes.value.persona?.estudiante?.registrosInternado || studentForNotes.value.persona?.estudiante?.registros_internado || [];
    if (selectedGestionIdForNotes.value === '') {
        return regs[0] || null;
    }
    return regs.find((r: any) => String(r.gestion_id) === String(selectedGestionIdForNotes.value)) || regs[0] || null;
});

const availableRegistrationsForNotes = computed(() => {
    if (!studentForNotes.value) return [];
    const regs = studentForNotes.value.persona?.estudiante?.registrosInternado || studentForNotes.value.persona?.estudiante?.registros_internado || [];
    
    // Sort descending by gestion name (year) so that the current/newest gestion is first
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

const openNotesModal = (user: any) => {
    studentForNotes.value = user;
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
                only: ['users'],
                onSuccess: () => {
                    subiendoBoletinAdmin.value = false;
                    subiendoBoletinPeriodo.value = null;
                    const updatedUser = props.users.find(u => u.id === studentForNotes.value.id);
                    if (updatedUser) {
                        studentForNotes.value = updatedUser;
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
                    only: ['users'],
                    onSuccess: () => {
                        const updatedUser = props.users.find(u => u.id === studentForNotes.value.id);
                        if (updatedUser) {
                            studentForNotes.value = updatedUser;
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
    if (target.files && target.files[0] && form.estudiante_id) {
        const file = target.files[0];
        
        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen no debe superar los 2MB');
            return;
        }

        subiendoFoto.value = true;
        
        const fileFormData = new FormData();
        fileFormData.append('foto', file);

        axios.post(route('estudiantes.foto', form.estudiante_id), fileFormData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        }).then(response => {
            form.ruta_foto = response.data.ruta_foto;
            const userIdx = props.users.findIndex(u => u.id === form.id);
            if (userIdx !== -1 && props.users[userIdx].persona?.estudiante) {
                props.users[userIdx].persona.estudiante.ruta_foto = response.data.ruta_foto;
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

const activeStudents = computed(() => {
    return props.users.filter(u => 
        u.rol?.nombre === 'Estudiante' && 
        u.estado_cuenta === 'Aprobado' && 
        u.persona?.estudiante?.estado_global === 'Activo'
    );
});

const promotionForm = useForm({
    nombre_gestion: '',
    fecha_inicio: '',
    fecha_fin: '',
    tipo_periodo_academico: 'Trimestre',
    cantidad_periodos: 3,
    promociones: [] as any[]
});

const openPromotionWizard = () => {
    filterCursoWizard.value = '';
    let nextYear = new Date().getFullYear();
    const firstStudent = props.users.find(u => getLatestReg(u));
    
    if (firstStudent && firstStudent.persona?.estudiante) {
        const regFirst = getLatestReg(firstStudent);
        const currentName = regFirst?.gestion?.nombre_gestion;
        if (currentName) {
            const match = currentName.match(/\d+/);
            if (match) {
                nextYear = parseInt(match[0]) + 1;
            }
        }
    }
    
    promotionForm.nombre_gestion = String(nextYear);
    promotionForm.fecha_inicio = `${nextYear}-02-01`;
    promotionForm.fecha_fin = `${nextYear}-11-30`;
    promotionForm.tipo_periodo_academico = 'Trimestre';
    promotionForm.cantidad_periodos = 3;
    
    promotionForm.promociones = activeStudents.value.map(user => {
        const est = user.persona?.estudiante;
        const regActual = getLatestReg(user);
        
        const currentCursoId = regActual?.curso_id || '';
        const currentCursoBthId = regActual?.curso_bth_id || '';
        
        let nextCursoId = currentCursoId;
        if (currentCursoId) {
            const curIdx = props.cursos.findIndex(c => String(c.id) === String(currentCursoId));
            if (curIdx !== -1 && curIdx + 1 < props.cursos.length) {
                nextCursoId = props.cursos[curIdx + 1].id;
            }
        }
        
        return {
            estudiante_id: est.id,
            nombre_completo: `${user.persona?.nombre} ${user.persona?.ap_paterno} ${user.persona?.ap_materno}`,
            ci: user.persona?.ci,
            curso_actual_nombre: regActual?.curso?.nombre || 'Ninguno',
            estado_anual: 'Aprobado',
            curso_id: nextCursoId,
            curso_bth_id: currentCursoBthId
        };
    });
    
    showPromotionModal.value = true;
};

const handleStatusChange = (index: number) => {
    const row = promotionForm.promociones[index];
    const user = activeStudents.value.find(u => u.persona?.estudiante?.id === row.estudiante_id);
    const regActual = getLatestReg(user);
    
    if (row.estado_anual === 'Reprobado') {
        row.curso_id = regActual?.curso_id || '';
    } else if (row.estado_anual === 'Retirado') {
        row.curso_id = '';
        row.curso_bth_id = '';
    } else if (row.estado_anual === 'Aprobado') {
        const currentCursoId = regActual?.curso_id || '';
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
    if (confirm('¿Está seguro de cerrar la gestión actual e inscribir/promocionar a todos los estudiantes al nuevo ciclo? Esta operación es irreversible.')) {
        promotionForm.post(route('gestiones.promocionar'), {
            onSuccess: () => {
                showPromotionModal.value = false;
                alert('Gestión académica promocionada y estudiantes inscritos exitosamente.');
            },
            onError: (err) => {
                console.error(err);
                alert('Ocurrió un error al realizar la promoción.');
            }
        });
    }
};

const handleEditFromView = (user: any) => {
    closeViewModal();
    setTimeout(() => {
        openEditModal(user);
    }, 150);
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
        }
    });
};

const rechazarUsuario = (id: number) => {
    if (confirm('¿Estás seguro de que deseas rechazar a este usuario?')) {
        router.put(route('users.rechazar', id));
    }
};

const aprobarMasivo = () => {
    if (confirm(`¿Aprobar ${selectedUsers.value.length} usuarios seleccionados?`)) {
        router.post(route('users.aprobar.masivo'), { user_ids: selectedUsers.value }, {
            onSuccess: () => {
                selectedUsers.value = [];
            }
        });
    }
};
</script>

<template>
    <Head title="Gestión de Personas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                Gestión de Usuarios y Personas
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Barra de Super Filtros -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-800">Filtros y Búsqueda Avanzada</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200 animate-pulse tracking-wide uppercase">
                                    Gestión Activa: {{ activeGestionName }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-slate-100 text-slate-800 border border-slate-200 uppercase tracking-wide">
                                    Cantidad: {{ filteredUsers.length }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">Busca y filtra estudiantes de manera eficiente por sus atributos y estados</p>
                        </div>
                        <div class="flex items-center gap-3 w-full md:w-auto justify-end flex-wrap">
                            <button v-if="selectedUsers.length > 0" @click="aprobarMasivo" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-xl shadow-sm transition flex items-center gap-2 text-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Aprobar {{ selectedUsers.length }} Usuarios
                            </button>
                            <button @click="openPromotionWizard" class="bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white font-bold py-2 px-5 rounded-xl shadow-md transition text-sm flex items-center gap-1.5 transform hover:scale-102">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                Promocionar Gestión Académica
                            </button>
                            <button @click="exportToExcel" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-5 rounded-xl shadow-md transition text-sm flex items-center gap-2 transform hover:scale-102" title="Descargar reporte Excel filtrado">
                                <svg class="w-4 h-4 text-emerald-100" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-4H8V8h5v2z"/>
                                </svg>
                                📊 Descargar Excel
                            </button>
                            <button @click="openCreateModal" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded-xl shadow-sm transition text-sm">
                                + Nuevo Usuario
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
                        <div class="lg:col-span-1">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Buscar</label>
                            <div class="relative mt-1">
                                <input v-model="filter.texto" type="text" placeholder="CI, Nombre, Cel..." class="w-full pl-8 pr-3 py-1.5 border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Estado / Historial</label>
                            <select v-model="filter.estado_cuenta" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Todos los Estados</option>
                                <option value="Pendiente">Pendiente de Habilitación</option>
                                <option value="Activo">Estudiante Activo (Cursando)</option>
                                <option value="Retirado">Retirado / Inactivo</option>
                                <option value="Bachiller">Bachiller</option>
                                <option value="Graduado BTH">Graduado BTH</option>
                                <option value="Rechazado">Rechazado</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo Persona</label>
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

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Pabellón</label>
                            <select v-model="filter.pabellon" class="mt-1 block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Todos</option>
                                <option value="Pabellón A">Pabellón A</option>
                                <option value="Pabellón B">Pabellón B</option>
                                <option value="Pabellón C">Pabellón C</option>
                                <option value="Pabellón D">Pabellón D</option>
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
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Grado (Curso)</label>
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
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-10">
                                        <input type="checkbox" v-model="checkAll" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Persona</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Contacto</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Detalles (Estudiantes)</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Rol / Estado</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50 transition" :class="{'bg-teal-50/50': selectedUsers.includes(user.id)}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input v-if="user.estado_cuenta === 'Pendiente'" type="checkbox" :value="user.id" v-model="selectedUsers" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-800 font-bold">
                                                    {{ user.persona?.nombre?.charAt(0) }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-gray-900">{{ user.persona?.nombre }} {{ user.persona?.ap_paterno }}</div>
                                                <div class="text-xs text-gray-500">CI: {{ user.persona?.ci }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">Cel: {{ user.persona?.celular || 'S/N' }}</div>
                                        <div class="text-xs text-gray-500">{{ user.email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <template v-if="user.rol?.nombre === 'Estudiante' && user.persona?.estudiante">
                                            <div class="text-xs text-gray-700">
                                                <strong>Comunidad:</strong> {{ user.persona.estudiante.comunidad?.nombre || 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-700 mt-1">
                                                <strong>Tutor Cel:</strong> {{ user.persona.estudiante.tutor?.persona?.celular || 'N/A' }}
                                            </div>
                                            <div v-if="user.persona.estudiante.año_egreso_bth" class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                Egresado BTH ({{ user.persona.estudiante.año_egreso_bth }})
                                            </div>
                                        </template>
                                        <span v-else class="text-xs text-gray-400">N/A</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col space-y-1">
                                            <span :class="{
                                                'bg-purple-100 text-purple-800': user.rol?.nombre === 'Superadmin',
                                                'bg-blue-100 text-blue-800': user.rol?.nombre === 'Encargada',
                                                'bg-green-100 text-green-800': user.rol?.nombre === 'Estudiante'
                                            }" class="px-2 inline-flex text-[11px] leading-4 font-black rounded-full self-start">
                                                {{ user.rol?.nombre || 'Sin Rol' }}
                                            </span>
                                            
                                            <span v-if="user.estado_cuenta === 'Pendiente'" class="px-2 inline-flex text-[10px] leading-4 font-bold rounded-full bg-yellow-100 text-yellow-800 self-start">
                                                Pendiente Habilitación
                                            </span>
                                            <span v-else-if="user.estado_cuenta === 'Rechazado'" class="px-2 inline-flex text-[10px] leading-4 font-bold rounded-full bg-red-100 text-red-800 self-start">
                                                Rechazado
                                            </span>
                                            <span v-else-if="user.rol?.nombre === 'Estudiante' && user.persona?.estudiante" :class="{
                                                'bg-green-100 text-green-800': !user.persona.estudiante.estado_global || user.persona.estudiante.estado_global === 'Activo',
                                                'bg-gray-100 text-gray-800': user.persona.estudiante.estado_global === 'Retirado',
                                                'bg-blue-100 text-blue-800': user.persona.estudiante.estado_global === 'Bachiller',
                                                'bg-indigo-100 text-indigo-800': user.persona.estudiante.estado_global === 'Graduado BTH'
                                            }" class="px-2 inline-flex text-[10px] leading-4 font-semibold rounded-full self-start">
                                                {{ user.persona.estudiante.estado_global || 'Activo' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex justify-end items-center gap-3">
                                        
                                        <!-- Botón Ver Detalle (Siempre disponible) -->
                                        <button @click="openViewModal(user)" class="text-amber-600 hover:text-amber-900 bg-amber-50 p-2 rounded-lg" title="Ver Ficha Completa">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </button>

                                        <!-- Botones para estado Pendiente -->
                                        <template v-if="user.estado_cuenta === 'Pendiente'">
                                            <button @click="openEditModal(user)" class="text-green-600 hover:text-green-900 bg-green-50 p-2 rounded-lg" title="Llenar datos y Aprobar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                            <button @click="rechazarUsuario(user.id)" class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg" title="Rechazar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </template>

                                        <!-- Controles Normales (Solo si no está rechazado) -->
                                        <template v-if="user.estado_cuenta !== 'Rechazado'">
                                            <button @click="openPasswordModal(user)" class="text-blue-600 hover:text-blue-900 bg-blue-50 p-2 rounded-lg" title="Cambiar Contraseña">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                            </button>
                                            <!-- Botón Notas (Solo para Estudiantes) -->
                                            <button v-if="user.rol?.nombre === 'Estudiante'" @click="openNotesModal(user)" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg" title="Ver y Gestionar Boletines de Calificaciones (Notas)">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </button>
                                            <button @click="openEditModal(user)" class="text-teal-600 hover:text-teal-900 bg-teal-50 p-2 rounded-lg" title="Editar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                        </template>
                                        
                                        <!-- Eliminar siempre disponible -->
                                        <button @click="confirmDeleteUser(user.id)" class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg" title="Eliminar del Sistema">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredUsers.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        No se encontraron resultados para la búsqueda.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal CRUD -->
                <div v-if="showModal" class="fixed z-50 inset-0 overflow-y-auto">
<div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="closeModal"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">

                            <form @submit.prevent="submitForm">
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[80vh] overflow-y-auto">

                                    <h3 class="text-xl leading-6 font-bold text-gray-900 mb-6 border-b pb-4">
                                        {{ isEditing ? 'Editar Registro' : 'Nuevo Registro' }}
                                    </h3>

                                    <!-- Alerta de Errores de Validación General -->
                                    <div v-if="Object.keys(form.errors).length > 0" class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-semibold text-red-800">Hay errores de validación en el formulario:</h3>
                                                <ul class="mt-1 list-disc list-inside text-xs text-red-700">
                                                    <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <fieldset :disabled="isReadOnlyMode" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        
                                        <!-- FOTO DE PERFIL (SOLO EN EDICION DE ESTUDIANTE EXISTENTE) -->
                                        <div v-if="isEditing && isEstudianteRole" class="md:col-span-2 bg-slate-50 p-4 rounded-xl border border-slate-200 mb-4 flex flex-col sm:flex-row items-center gap-4">
                                            <div class="w-24 h-32 bg-gray-200 border-2 border-gray-300 rounded-lg overflow-hidden flex items-center justify-center shrink-0 shadow-inner relative group">
                                                <img v-if="form.ruta_foto" :src="form.ruta_foto.startsWith('http') || form.ruta_foto.startsWith('/') ? form.ruta_foto : '/storage/' + form.ruta_foto" class="w-full h-full object-cover">
                                                <svg v-else class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                            </div>
                                            <div class="flex-1 text-center sm:text-left">
                                                <h4 class="font-bold text-gray-800 text-sm mb-1">Foto de Perfil del Estudiante</h4>
                                                <p class="text-xs text-gray-500 mb-3">Sube o cambia la foto oficial del estudiante para su credencial. Formato JPG o PNG, máximo 2MB.</p>
                                                <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                                                    <label class="cursor-pointer bg-teal-600 hover:bg-teal-700 text-white font-bold py-1.5 px-3 rounded-lg text-xs transition duration-200 shadow-sm flex items-center gap-1">
                                                        <span>📷 {{ form.ruta_foto ? 'Cambiar Foto' : 'Subir Foto' }}</span>
                                                        <input type="file" class="hidden" accept="image/*" @change="adminSubirFoto">
                                                    </label>
                                                    <span v-if="subiendoFoto" class="text-xs text-teal-600 font-bold self-center animate-pulse">Subiendo...</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- DATOS PERSONALES -->
                                        <div class="md:col-span-2">
                                            <h4 class="font-bold text-teal-700 mb-2 uppercase text-sm tracking-wide border-b pb-1">1. Datos Personales Básicos</h4>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Carnet de Identidad (CI)</label>
                                            <input v-model="form.ci" :disabled="isEditing" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                            <p v-if="form.errors.ci" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.ci }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Nombre(s)</label>
                                            <input v-model="form.nombre" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                            <p v-if="form.errors.nombre" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.nombre }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Apellido Paterno</label>
                                            <input v-model="form.ap_paterno" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                            <p v-if="form.errors.ap_paterno" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.ap_paterno }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Apellido Materno</label>
                                            <input v-model="form.ap_materno" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                            <p v-if="form.errors.ap_materno" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.ap_materno }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Celular Propio</label>
                                            <input v-model="form.celular" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                            <p v-if="form.errors.celular" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.celular }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Género</label>
                                            <select v-model="form.sexo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                                <option value="">Seleccione...</option>
                                                <option value="M">Masculino</option>
                                                <option value="F">Femenino</option>
                                            </select>
                                            <p v-if="form.errors.sexo" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.sexo }}</p>
                                        </div>

                                        <!-- SISTEMA -->
                                        <div class="md:col-span-2 mt-4">
                                            <h4 class="font-bold text-teal-700 mb-2 uppercase text-sm tracking-wide border-b pb-1">2. Acceso al Sistema</h4>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                                            <input v-model="form.email" type="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                            <p v-if="form.errors.email" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.email }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Rol del Sistema</label>
                                            <select v-model="form.rol_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm bg-blue-50 border-blue-200">
                                                <option disabled value="">Seleccionar Rol...</option>
                                                <option v-for="rol in roles" :key="rol.id" :value="rol.id">{{ rol.nombre }}</option>
                                            </select>
                                            <p v-if="form.errors.rol_id" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.rol_id }}</p>
                                        </div>

                                        <!-- ESTUDIANTES -->
                                        <template v-if="isEstudianteRole">
                                            <div class="md:col-span-2 mt-4">
                                                <h4 class="font-bold text-teal-700 mb-2 uppercase text-sm tracking-wide border-b pb-1">3. Datos de Internado (Ubicación y Cuarto)</h4>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Provincia de Origen</label>
                                                <select v-model="form.provincia_id" @change="form.comunidad_id = ''" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                                    <option value="">Seleccione...</option>
                                                    <option v-for="prov in provincias" :key="prov.id" :value="prov.id">{{ prov.nombre }}</option>
                                                </select>
                                                <p v-if="form.errors.provincia_id" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.provincia_id }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Comunidad</label>
                                                <select v-model="form.comunidad_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm" :disabled="!form.provincia_id">
                                                    <option value="">Seleccione...</option>
                                                    <option v-for="com in comunidadesForm" :key="com.id" :value="com.id">{{ com.nombre }}</option>
                                                </select>
                                                <p v-if="form.errors.comunidad_id" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.comunidad_id }}</p>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Pabellón Designado</label>
                                                <select v-model="form.pabellon" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                                    <option value="">Ninguno</option>
                                                    <option value="Pabellón A">Pabellón A</option>
                                                    <option value="Pabellón B">Pabellón B</option>
                                                    <option value="Pabellón C">Pabellón C</option>
                                                    <option value="Pabellón D">Pabellón D</option>
                                                </select>
                                                <p v-if="form.errors.pabellon" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.pabellon }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Número de Cama</label>
                                                <input v-model="form.cama" type="text" placeholder="Ej. 12" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                                <p v-if="form.errors.cama" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.cama }}</p>
                                            </div>

                                            <div class="md:col-span-2 mt-4">
                                                <h4 class="font-bold text-teal-700 mb-2 uppercase text-sm tracking-wide border-b pb-1">4. Datos Académicos</h4>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Grado Regular</label>
                                                <select v-model="form.curso_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                                    <option value="">Ninguno</option>
                                                    <option v-for="curso in cursos" :key="curso.id" :value="curso.id">{{ curso.nombre }}</option>
                                                </select>
                                                <p v-if="form.errors.curso_id" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.curso_id }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Curso BTH</label>
                                                <select v-model="form.curso_bth_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                                    <option value="">Ninguno</option>
                                                    <option v-for="bth in cursosBth" :key="bth.id" :value="bth.id">{{ bth.carrera_tecnica?.nombre }} - {{ bth.nivel }}</option>
                                                </select>
                                                <p v-if="form.errors.curso_bth_id" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.curso_bth_id }}</p>
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700">Año Egreso BTH (Si ya se graduó de BTH)</label>
                                                <input v-model="form.año_egreso_bth" type="number" placeholder="Ej. 2025" class="mt-1 block w-full md:w-1/2 border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                                <p v-if="form.errors.año_egreso_bth" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.año_egreso_bth }}</p>
                                            </div>

                                            <div class="md:col-span-2 mt-2 bg-gray-50 p-4 rounded-xl border border-gray-100">
                                                <h4 class="font-bold text-gray-800 mb-4 uppercase text-sm tracking-wide">Historial del Estudiante</h4>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Estado Global</label>
                                                        <select v-model="form.estado_global" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm focus:ring-teal-500 focus:border-teal-500">
                                                            <option value="Activo">Activo</option>
                                                            <option value="Retirado">Retirado</option>
                                                            <option value="Bachiller">Bachiller</option>
                                                            <option value="Graduado BTH">Graduado BTH</option>
                                                        </select>
                                                        <p v-if="form.errors.estado_global" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.estado_global }}</p>
                                                    </div>
                                                    <div v-if="form.estado_global === 'Bachiller' || form.estado_global === 'Graduado BTH'">
                                                        <label class="block text-sm font-medium text-gray-700">Año Egreso Bachiller</label>
                                                        <input v-model="form.año_egreso_bachiller" type="number" placeholder="Ej. 2025" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                                        <p v-if="form.errors.año_egreso_bachiller" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.año_egreso_bachiller }}</p>
                                                    </div>
                                                    <div v-if="form.estado_global === 'Graduado BTH'">
                                                        <label class="block text-sm font-medium text-gray-700">Año Egreso BTH</label>
                                                        <input v-model="form.año_egreso_bth" type="number" placeholder="Ej. 2025" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                                                        <p v-if="form.errors.año_egreso_bth" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.año_egreso_bth }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="md:col-span-2 mt-4 bg-yellow-50 p-4 rounded-xl border border-yellow-100">
                                                <h4 class="font-bold text-yellow-800 mb-4 uppercase text-sm tracking-wide">5. Datos del Tutor / Apoderado (Opcional)</h4>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">CI del Tutor</label>
                                                        <input v-model="form.tutor_ci" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                                                        <p v-if="form.errors.tutor_ci" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.tutor_ci }}</p>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Celular (WhatsApp)</label>
                                                        <input v-model="form.tutor_celular" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                                                        <p v-if="form.errors.tutor_celular" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.tutor_celular }}</p>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Nombre</label>
                                                        <input v-model="form.tutor_nombre" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                                                        <p v-if="form.errors.tutor_nombre" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.tutor_nombre }}</p>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Ap. Paterno</label>
                                                        <input v-model="form.tutor_ap_paterno" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                                                        <p v-if="form.errors.tutor_ap_paterno" class="text-red-500 text-xs mt-1 font-semibold">{{ form.errors.tutor_ap_paterno }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                    </fieldset>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-2xl border-t">
                                    <template v-if="isReadOnlyMode && isEditing">
                                        <button @click="habilitarModificacion" type="button" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-amber-500 text-base font-medium text-white hover:bg-amber-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                            Modificar Ficha
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                            {{ form.processing ? 'Guardando...' : (form.estado_cuenta === 'Pendiente' ? 'Aprobar y Guardar' : 'Guardar Ficha Completa') }}
                                        </button>
                                    </template>
                                    <button @click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                        Cancelar
                                    </button>
                                </div>
                            </form>

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
                                    <h3 class="text-xl font-bold text-gray-900 mb-6">Cambiar Contraseña</h3>
                                    <p class="text-sm text-gray-500 mb-4">Nueva contraseña para <strong>{{ userForPassword?.persona?.nombre }}</strong></p>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
                                            <input type="password" v-model="formPassword.password" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                            <p class="text-xs text-red-600 mt-1" v-if="formPassword.errors.password">{{ formPassword.errors.password }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
                                            <input type="password" v-model="formPassword.password_confirmation" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse rounded-b-2xl">
                                    <button type="submit" :disabled="formPassword.processing" class="w-full inline-flex justify-center rounded-lg shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 sm:ml-3 sm:w-auto sm:text-sm">
                                        {{ formPassword.processing ? 'Guardando...' : 'Cambiar' }}
                                    </button>
                                    <button @click="showPasswordModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal de Gestión de Notas / Boletines -->
                <div v-if="showNotesModal && studentForNotes" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <!-- Backdrop -->
                        <div class="fixed inset-0 transition-opacity backdrop-blur-md bg-slate-900/60" @click="closeNotesModal"></div>
                        
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        
                        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-100">
                            <!-- Cabecera -->
                            <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-white flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-indigo-100 text-indigo-700 rounded-xl">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-black text-slate-800 leading-tight">Boletines de Calificaciones</h3>
                                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">{{ studentForNotes.persona?.nombre }} {{ studentForNotes.persona?.ap_paterno }}</p>
                                    </div>
                                </div>
                                <button @click="closeNotesModal" class="text-slate-400 hover:text-slate-600 transition p-1 bg-slate-50 rounded-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            
                            <!-- Contenido -->
                            <div class="p-6 space-y-6">
                                <!-- Selector de Gestión Académica (Año) -->
                                <div>
                                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Seleccionar Gestión Académica</label>
                                    <select v-model="selectedGestionIdForNotes" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-bold text-slate-700 bg-slate-50/50">
                                        <option v-for="reg in availableRegistrationsForNotes" :key="reg.id" :value="reg.gestion_id">
                                            Gestión {{ reg.gestion?.nombre_gestion }} — {{ reg.curso?.nombre || 'Curso N/A' }}
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Información del Registro Académico -->
                                <div v-if="selectedRegForNotes" class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex flex-wrap gap-4 text-xs text-slate-600 font-medium">
                                    <div><strong>Estado Anual:</strong> <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase" :class="selectedRegForNotes.estado_anual === 'Cursando' ? 'bg-indigo-100 text-indigo-800' : selectedRegForNotes.estado_anual === 'Aprobado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">{{ selectedRegForNotes.estado_anual }}</span></div>
                                    <div><strong>Ubicación:</strong> {{ selectedRegForNotes.pabellon || 'N/A' }} — Cama {{ selectedRegForNotes.cama || 'N/A' }}</div>
                                    <div><strong>BTH Especialidad:</strong> {{ selectedRegForNotes.curso_bth?.carrera_tecnica?.nombre || 'No asignado' }}</div>
                                </div>
                                
                                <!-- Listado de Periodos y Boletines -->
                                <div v-if="selectedRegForNotes" class="space-y-4">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-1">Periodos de Calificaciones</h4>
                                    
                                    <div class="grid grid-cols-1 gap-4">
                                        <div v-for="period in (selectedRegForNotes.gestion?.cantidad_periodos || 3)" :key="period" class="p-4 border border-slate-100 rounded-2xl flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/30 hover:bg-slate-50 transition duration-150">
                                            <div class="text-center sm:text-left">
                                                <p class="font-black text-slate-800 text-sm">{{ selectedRegForNotes.gestion?.tipo_periodo_academico }} {{ period }}</p>
                                                <p class="text-xs text-slate-400 font-medium">Calificaciones oficiales del periodo</p>
                                            </div>
                                            
                                            <!-- Acciones -->
                                            <div class="flex items-center gap-2">
                                                <!-- Boletín existe -->
                                                <template v-if="selectedRegForNotes.boletines?.find(b => b.numero_periodo === period)">
                                                    <a :href="'/storage/' + selectedRegForNotes.boletines.find(b => b.numero_periodo === period).ruta_archivo" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-sm transition">
                                                        <span>👁️ Ver Notas</span>
                                                    </a>
                                                    <button @click="adminDeleteBoletin(selectedRegForNotes.boletines.find(b => b.numero_periodo === period).id)" class="p-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl transition" title="Eliminar boletín permanentemente">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </template>
                                                
                                                <!-- Boletín no existe -->
                                                <template v-else>
                                                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm transition">
                                                        <span>📤 Subir Notas</span>
                                                        <input type="file" class="hidden" accept=".pdf,image/*" @change="adminUploadBoletin($event, period)" :disabled="subiendoBoletinAdmin && subiendoBoletinPeriodo === period">
                                                    </label>
                                                    <span v-if="subiendoBoletinAdmin && subiendoBoletinPeriodo === period" class="text-xs text-indigo-600 font-bold animate-pulse">Subiendo...</span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center text-slate-400 py-6 text-sm font-semibold">
                                    Este estudiante no cuenta con gestiones académicas registradas.
                                </div>
                            </div>
                            
                            <!-- Pie -->
                            <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse border-t border-slate-100 rounded-b-3xl">
                                <button @click="closeNotesModal" class="w-full inline-flex justify-center rounded-xl border border-slate-200 shadow-sm px-4 py-2 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 sm:w-auto">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Vista Completa (Ver Ficha Completa) -->
                <div v-if="showViewModal && selectedUser" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <!-- Backdrop -->
                        <div class="fixed inset-0 transition-opacity backdrop-blur-md bg-slate-900/60" @click="closeViewModal"></div>
                        
                        <!-- Element to trick browser into centering the modal contents. -->
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        
                        <!-- Modal Content -->
                        <div class="inline-block align-bottom bg-slate-50 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-200">
                            
                            <!-- Premium Header Banner with Gradient -->
                            <div class="relative bg-gradient-to-r from-teal-600 via-cyan-600 to-indigo-600 px-6 py-8 sm:px-8 text-white">
                                <div class="absolute top-4 right-4">
                                    <button @click="closeViewModal" class="text-white/80 hover:text-white bg-white/10 hover:bg-white/20 p-2 rounded-full transition-all duration-200">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                
                                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 text-center sm:text-left">
                                    <!-- Avatar / Profile Picture -->
                                    <div class="relative group flex-shrink-0">
                                        <div class="w-24 h-24 rounded-2xl bg-white/10 backdrop-blur-sm border-2 border-white/40 flex items-center justify-center overflow-hidden shadow-inner transform group-hover:scale-105 transition-all duration-300">
                                            <img v-if="selectedUser.persona?.estudiante?.ruta_foto" :src="selectedUser.persona.estudiante.ruta_foto.startsWith('http') || selectedUser.persona.estudiante.ruta_foto.startsWith('/') ? selectedUser.persona.estudiante.ruta_foto : '/storage/' + selectedUser.persona.estudiante.ruta_foto" alt="Foto perfil" class="w-full h-full object-cover">
                                            <span v-else class="text-3xl font-extrabold tracking-wider text-teal-100 uppercase">
                                                {{ selectedUser.persona?.nombre?.charAt(0) }}{{ selectedUser.persona?.ap_paterno?.charAt(0) }}
                                            </span>
                                        </div>
                                        <div class="absolute -bottom-2 -right-2 bg-emerald-500 text-white p-1.5 rounded-lg shadow border border-white flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                    </div>
                                    
                                    <!-- Header Info -->
                                    <div class="space-y-2 flex-grow">
                                        <div class="flex flex-wrap justify-center sm:justify-start items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-white/25 text-white backdrop-blur-sm border border-white/20">
                                                {{ selectedUser.rol?.nombre }}
                                            </span>
                                            
                                            <!-- Account Status -->
                                            <span :class="{
                                                'bg-emerald-500/20 text-emerald-200 border-emerald-500/30': selectedUser.estado_cuenta === 'Aprobado',
                                                'bg-amber-500/20 text-amber-200 border-amber-500/30': selectedUser.estado_cuenta === 'Pendiente',
                                                'bg-rose-500/20 text-rose-200 border-rose-500/30': selectedUser.estado_cuenta === 'Rechazado'
                                            }" class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider border">
                                                Cuenta: {{ selectedUser.estado_cuenta }}
                                            </span>
 
                                            <!-- Global Academic Status -->
                                            <span v-if="selectedUser.persona?.estudiante" :class="{
                                                'bg-sky-500/20 text-sky-200 border-sky-500/30': selectedUser.persona.estudiante.estado_global === 'Activo',
                                                'bg-amber-600/20 text-amber-200 border-amber-600/30': selectedUser.persona.estudiante.estado_global === 'Retirado',
                                                'bg-indigo-500/20 text-indigo-200 border-indigo-500/30': selectedUser.persona.estudiante.estado_global === 'Bachiller',
                                                'bg-purple-500/20 text-purple-200 border-purple-500/30': selectedUser.persona.estudiante.estado_global === 'Graduado BTH'
                                            }" class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider border">
                                                {{ selectedUser.persona.estudiante.estado_global }}
                                            </span>
                                        </div>
                                        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                                            {{ selectedUser.persona?.nombre }} {{ selectedUser.persona?.ap_paterno }} {{ selectedUser.persona?.ap_materno }}
                                        </h2>
                                        
                                        <!-- Curso y Gestión Activa del Egresado/Estudiante -->
                                        <p v-if="selectedUser.persona?.estudiante" class="text-teal-100 text-xs font-bold uppercase tracking-widest flex flex-wrap items-center justify-center sm:justify-start gap-2.5">
                                            <span class="inline-flex items-center gap-1">🎒 Curso: <span class="text-white font-black">{{ getLatestReg(selectedUser)?.curso?.nombre || 'S/N' }}</span></span>
                                            <span class="opacity-50">•</span>
                                            <span class="inline-flex items-center gap-1">📅 Gestión: <span class="text-white font-black">{{ getLatestReg(selectedUser)?.gestion?.nombre_gestion || 'S/N' }}</span></span>
                                        </p>
                                        
                                        <p class="text-teal-100/90 text-sm font-medium flex items-center justify-center sm:justify-start gap-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                            {{ selectedUser.email }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Body grid -->
                            <div class="px-6 py-8 sm:px-8 max-h-[60vh] overflow-y-auto space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    
                                    <!-- CARD 1: DATOS PERSONALES -->
                                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow transition-shadow duration-200 space-y-4">
                                        <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                                            <div class="p-1.5 rounded-lg bg-teal-50 text-teal-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            </div>
                                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Datos Personales</h3>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">C.I. / Documento</span>
                                                <span class="font-bold text-slate-700">{{ selectedUser.persona?.ci }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">Género</span>
                                                <span class="font-bold text-slate-700">
                                                    {{ selectedUser.persona?.sexo === 'M' ? 'Masculino' : (selectedUser.persona?.sexo === 'F' ? 'Femenino' : 'No especificado') }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">Celular</span>
                                                <span class="font-bold text-slate-700 flex items-center gap-1">
                                                    {{ selectedUser.persona?.celular || 'Sin registrar' }}
                                                    <a v-if="selectedUser.persona?.celular" :href="'https://wa.me/591' + selectedUser.persona.celular" target="_blank" class="text-emerald-500 hover:text-emerald-600" title="Contactar por WhatsApp">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.73-1.455L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.97C16.528 2.012 14.07 1.01 11.998 1.01c-5.44 0-9.866 4.372-9.87 9.802 0 1.714.46 3.39 1.331 4.869L2.525 21.27l5.962-1.556z"/></svg>
                                                    </a>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">Fecha Registro</span>
                                                <span class="font-bold text-slate-700">{{ new Date(selectedUser.created_at).toLocaleDateString() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- CARD 2: UBICACIÓN Y RESIDENCIA -->
                                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow transition-shadow duration-200 space-y-4">
                                        <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                                            <div class="p-1.5 rounded-lg bg-teal-50 text-teal-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            </div>
                                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Residencia e Internado</h3>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">Provincia de Origen</span>
                                                <span class="font-bold text-slate-700">{{ getProvinciaName(selectedUser.persona?.estudiante?.comunidad_id) || 'No especificado' }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">Comunidad</span>
                                                <span class="font-bold text-slate-700">{{ selectedUser.persona?.estudiante?.comunidad?.nombre || 'No especificado' }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">Pabellón Designado</span>
                                                <span class="font-bold text-slate-700 flex items-center gap-1.5">
                                                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                                    {{ getLatestReg(selectedUser)?.pabellon || 'Sin asignar' }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">Número de Cama</span>
                                                <span class="font-bold text-slate-700">{{ getLatestReg(selectedUser)?.cama || 'Sin asignar' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- CARD 3: SITUACIÓN ACADÉMICA -->
                                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow transition-shadow duration-200 space-y-4 md:col-span-2">
                                        <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                                            <div class="p-1.5 rounded-lg bg-teal-50 text-teal-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                            </div>
                                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Historial y Grado Académico</h3>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">Grado Regular Interno</span>
                                                <span class="font-bold text-slate-800 text-base">
                                                    {{ getLatestReg(selectedUser)?.curso?.nombre || 'No asignado' }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">Curso Técnico BTH / Especialidad</span>
                                                <span class="font-bold text-slate-800 text-base block">
                                                    {{ getLatestReg(selectedUser)?.curso_bth?.carrera_tecnica?.nombre || 'No asignado' }}
                                                </span>
                                                <span v-if="getLatestReg(selectedUser)?.curso_bth?.nivel" class="text-xs text-indigo-600 font-semibold bg-indigo-50 px-2 py-0.5 rounded mt-1 inline-block">
                                                    Nivel BTH: {{ getLatestReg(selectedUser)?.curso_bth?.nivel }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">Años de Egreso / Graduación</span>
                                                <div class="space-y-1 mt-1 text-xs">
                                                    <div class="flex justify-between border-b border-slate-50 pb-1">
                                                        <span class="text-slate-500">Egreso Bachiller:</span>
                                                        <span class="font-bold text-slate-700">{{ selectedUser.persona?.estudiante?.año_egreso_bachiller || 'N/A' }}</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-slate-500">Egreso Técnico BTH:</span>
                                                        <span class="font-bold text-slate-700">{{ selectedUser.persona?.estudiante?.año_egreso_bth || 'N/A' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- CARD 4: DATOS DEL TUTOR -->
                                    <div v-if="selectedUser.persona?.estudiante?.tutor" class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow transition-shadow duration-200 space-y-4 md:col-span-2 bg-gradient-to-br from-amber-50/30 to-white">
                                        <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                                            <div class="p-1.5 rounded-lg bg-amber-50 text-amber-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                            </div>
                                            <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wider">Datos del Tutor / Apoderado</h3>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">Nombre Completo</span>
                                                <span class="font-bold text-slate-800 text-base">
                                                    {{ selectedUser.persona.estudiante.tutor.persona?.nombre }} {{ selectedUser.persona.estudiante.tutor.persona?.ap_paterno }} {{ selectedUser.persona.estudiante.tutor.persona?.ap_materno }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">CI del Tutor</span>
                                                <span class="font-bold text-slate-700 text-base">{{ selectedUser.persona.estudiante.tutor.persona_ci }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-semibold text-slate-400 uppercase">Contacto Directo</span>
                                                <div class="mt-1" v-if="selectedUser.persona.estudiante.tutor.persona?.celular">
                                                    <a :href="'https://wa.me/591' + selectedUser.persona.estudiante.tutor.persona.celular" target="_blank" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs transition-colors shadow">
                                                        <svg class="w-4.5 h-4.5 fill-white" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.73-1.455L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.97C16.528 2.012 14.07 1.01 11.998 1.01c-5.44 0-9.866 4.372-9.87 9.802 0 1.714.46 3.39 1.331 4.869L2.525 21.27l5.962-1.556z"/></svg>
                                                        WhatsApp: {{ selectedUser.persona.estudiante.tutor.persona.celular }}
                                                    </a>
                                                </div>
                                                <span v-else class="font-bold text-slate-400">Sin celular registrado</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="md:col-span-2 p-4 text-center border border-dashed border-amber-200 bg-amber-50/50 rounded-2xl text-amber-700 text-sm font-semibold">
                                        Este estudiante no tiene un tutor registrado aún.
                                    </div>

                                    <!-- SECCIÓN: LÍNEA DE TIEMPO / TRAYECTORIA ACADÉMICA (SOLO ESTUDIANTES) -->
                                    <div v-if="selectedUser.persona?.estudiante" class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow transition-shadow duration-200 space-y-4 md:col-span-2 bg-gradient-to-br from-teal-50/20 to-white mt-4">
                                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-100 pb-2">
                                            <div class="flex items-center gap-2">
                                                <div class="p-1.5 rounded-lg bg-teal-50 text-teal-600">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                                </div>
                                                <h3 class="text-sm font-bold text-teal-800 uppercase tracking-wider">Historial y Trayectoria Académica</h3>
                                            </div>
                                            <div class="flex gap-2 text-xs">
                                                <span class="px-2.5 py-1 bg-teal-50 border border-teal-100 rounded-lg font-bold text-teal-700">
                                                    Ingreso: {{ (selectedUser.persona.estudiante.registrosInternado || selectedUser.persona.estudiante.registros_internado)?.[(selectedUser.persona.estudiante.registrosInternado || selectedUser.persona.estudiante.registros_internado).length - 1]?.curso?.nombre || 'S/N' }}
                                                </span>
                                                <span class="px-2.5 py-1 bg-blue-50 border border-blue-100 rounded-lg font-bold text-blue-700">
                                                    Años en Internado: {{ (selectedUser.persona.estudiante.registrosInternado || selectedUser.persona.estudiante.registros_internado)?.length || 0 }} {{ (selectedUser.persona.estudiante.registrosInternado || selectedUser.persona.estudiante.registros_internado)?.length === 1 ? 'Año' : 'Años' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div v-if="!sortedRegistrations(selectedUser) || sortedRegistrations(selectedUser).length === 0" class="text-center text-slate-400 py-6 text-sm">
                                            No se registran datos académicos o de gestión anteriores para este estudiante.
                                        </div>
                                        <div v-else class="relative border-l-2 border-teal-100 ml-4 pl-6 space-y-6 py-2">
                                            <div v-for="reg in sortedRegistrations(selectedUser)" :key="reg.id" class="relative group">
                                                <!-- Indicator dot -->
                                                <span class="absolute -left-[31px] top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-white border-2 transition-all duration-300"
                                                    :class="{
                                                        'border-teal-500 ring-2 ring-teal-50': reg.estado_anual === 'Cursando',
                                                        'border-green-500 bg-green-500': reg.estado_anual === 'Aprobado',
                                                        'border-red-500 bg-red-500': reg.estado_anual === 'Reprobado',
                                                        'border-gray-400 bg-gray-400': reg.estado_anual === 'Retirado'
                                                    }">
                                                    <span v-if="reg.estado_anual === 'Cursando'" class="h-1.5 w-1.5 rounded-full bg-teal-500 animate-ping"></span>
                                                </span>

                                                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 hover:shadow-xs transition duration-200">
                                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-2">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs font-black text-slate-700 bg-slate-200/60 px-2 py-0.5 rounded border border-slate-300">
                                                                Gestión: {{ reg.gestion?.nombre_gestion || 'S/N' }}
                                                            </span>
                                                            <h4 class="text-sm font-bold text-slate-800">{{ reg.curso?.nombre || 'Curso no Asignado' }}</h4>
                                                        </div>
                                                        <span :class="{
                                                            'bg-teal-50 text-teal-700 border-teal-200': reg.estado_anual === 'Cursando',
                                                            'bg-green-50 text-green-700 border-green-200': reg.estado_anual === 'Aprobado',
                                                            'bg-red-50 text-red-700 border-red-200': reg.estado_anual === 'Reprobado',
                                                            'bg-slate-100 text-slate-600 border-slate-200': reg.estado_anual === 'Retirado'
                                                        }" class="px-2 py-0.5 border text-[10px] font-bold uppercase tracking-wider rounded-full">
                                                            {{ reg.estado_anual }}
                                                        </span>
                                                    </div>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-500">
                                                        <div>🏢 <strong>Ubicación:</strong> Cama {{ reg.cama || 'S/N' }} ({{ reg.pabellon || 'S/N' }})</div>
                                                        <div>🎓 <strong>Carrera BTH:</strong> {{ reg.curso_bth?.carrera_tecnica?.nombre || reg.cursoBth?.carreraTecnica?.nombre || 'No Registra' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                            
                            <!-- Premium Footer with action buttons -->
                            <div class="bg-slate-100 px-6 py-4 sm:px-8 flex flex-row-reverse gap-3 rounded-b-3xl border-t border-slate-200">
                                <button @click="closeViewModal" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 focus:outline-none transition-all duration-200">
                                    Cerrar Vista
                                </button>
                                
                                <button v-if="selectedUser.estado_cuenta !== 'Rechazado'" @click="handleEditFromView(selectedUser)" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-xl shadow-sm px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-sm font-bold text-white focus:outline-none transition-all duration-200 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    Editar Ficha
                                </button>
                            </div>
                            
                        </div>
                    </div>
                </div>

                <!-- Modal Promoción y Cierre de Gestión -->
                <div v-if="showPromotionModal" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="showPromotionModal = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                            <form @submit.prevent="submitPromotion">
                                <div class="bg-white px-6 pt-6 pb-4 max-h-[85vh] overflow-y-auto">
                                    
                                    <div class="flex items-center gap-3 mb-6 border-b pb-4 bg-gradient-to-r from-indigo-500 to-blue-600 -mx-6 -mt-6 p-6 text-white">
                                        <div class="p-2.5 bg-white/20 rounded-xl">
                                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                        </div>
                                        <div>
                                            <h3 class="text-2xl font-black">Cierre de Gestión y Asistente de Promoción</h3>
                                            <p class="text-xs text-indigo-100">Cierra el año lectivo actual e inscribe de forma automática a los estudiantes al nuevo ciclo.</p>
                                        </div>
                                    </div>

                                    <!-- ALERTA DE ERRORES -->
                                    <div v-if="Object.keys(promotionForm.errors).length > 0" class="mb-6 bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-xl">
                                        <h4 class="text-sm font-bold text-rose-800">Errores detectados al promocionar:</h4>
                                        <ul class="list-disc list-inside text-xs text-rose-700 mt-1">
                                            <li v-for="(err, k) in promotionForm.errors" :key="k">{{ err }}</li>
                                        </ul>
                                    </div>

                                    <!-- DATOS DE LA NUEVA GESTION -->
                                    <div class="bg-slate-50 border border-slate-200 p-5 rounded-2xl mb-6">
                                        <h4 class="font-black text-slate-800 text-sm mb-4 uppercase tracking-wider border-b pb-2">1. Parámetros del Nuevo Año Académico</h4>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 uppercase">Nueva Gestión (Año)</label>
                                                <input type="text" v-model="promotionForm.nombre_gestion" placeholder="Ej. 2027" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 uppercase">Fecha de Inicio</label>
                                                <input type="date" v-model="promotionForm.fecha_inicio" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 uppercase">Fecha de Fin</label>
                                                <input type="date" v-model="promotionForm.fecha_fin" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 uppercase">Tipo de Periodo</label>
                                                <select v-model="promotionForm.tipo_periodo_academico" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                                    <option value="Trimestre">Trimestre</option>
                                                    <option value="Bimestre">Bimestre</option>
                                                    <option value="Semestre">Semestre</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 uppercase">Cantidad de Periodos</label>
                                                <input type="number" v-model="promotionForm.cantidad_periodos" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- GRID DE ESTUDIANTES -->
                                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                                        <div class="bg-slate-50 p-4 border-b border-slate-200 flex flex-wrap justify-between items-center gap-4">
                                            <div>
                                                <h4 class="font-black text-slate-800 text-sm uppercase tracking-wider">2. Planilla de Estudiantes a Promocionar ({{ promotionForm.promociones.length }})</h4>
                                                <span class="text-xs font-bold text-slate-500 bg-slate-200 px-2 py-0.5 rounded-full">Únicamente estudiantes actualmente Activos</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-black text-slate-600">Filtrar por Curso:</span>
                                                <select v-model="filterCursoWizard" class="rounded-lg text-xs py-1 px-3 border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white font-bold text-slate-700">
                                                    <option value="">[Todos los Cursos]</option>
                                                    <option v-for="c in cursos" :key="c.id" :value="c.nombre">{{ c.nombre }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="overflow-x-auto max-h-[40vh]">
                                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-xs sticky top-0">
                                                    <tr>
                                                        <th class="px-4 py-3 text-left">Estudiante</th>
                                                        <th class="px-4 py-3 text-left">Curso Actual</th>
                                                        <th class="px-4 py-3 text-left w-36">Resultado</th>
                                                        <th class="px-4 py-3 text-left w-48">Nuevo Grado (Curso)</th>
                                                        <th class="px-4 py-3 text-left w-64">Carrera Técnica BTH</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    <tr v-for="(promo, index) in promotionForm.promociones" :key="promo.estudiante_id" v-show="!filterCursoWizard || promo.curso_actual_nombre === filterCursoWizard" class="hover:bg-slate-50/50 transition duration-150">
                                                        <td class="px-4 py-3">
                                                            <div class="font-bold text-slate-800">{{ promo.nombre_completo }}</div>
                                                            <div class="text-xs text-slate-400">CI: {{ promo.ci }}</div>
                                                        </td>
                                                        <td class="px-4 py-3 text-slate-500 font-semibold">{{ promo.curso_actual_nombre }}</td>
                                                        <td class="px-4 py-3">
                                                            <select v-model="promo.estado_anual" @change="handleStatusChange(index)" 
                                                                :class="{
                                                                    'border-green-300 text-green-700 bg-green-50': promo.estado_anual === 'Aprobado',
                                                                    'border-amber-300 text-amber-700 bg-amber-50': promo.estado_anual === 'Reprobado',
                                                                    'border-slate-300 text-slate-700 bg-slate-50': promo.estado_anual === 'Retirado'
                                                                }"
                                                                class="rounded-lg text-xs font-bold py-1 w-full border shadow-sm">
                                                                <option value="Aprobado">Aprobado</option>
                                                                <option value="Reprobado">Reprobado</option>
                                                                <option value="Retirado">Retirado</option>
                                                            </select>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <select v-model="promo.curso_id" :disabled="promo.estado_anual === 'Retirado'" class="rounded-lg text-xs py-1 w-full border-gray-300 shadow-sm">
                                                                <option value="">Ninguno</option>
                                                                <option v-for="c in cursos" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                                                            </select>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <select v-model="promo.curso_bth_id" :disabled="promo.estado_anual === 'Retirado'" class="rounded-lg text-xs py-1 w-full border-gray-300 shadow-sm">
                                                                <option value="">Ninguna Especialidad</option>
                                                                <option v-for="b in cursosBth" :key="b.id" :value="b.id">{{ b.carrera_tecnica?.nombre }} - {{ b.nivel }}</option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse rounded-b-2xl gap-3 border-t border-slate-200">
                                    <button type="submit" :disabled="promotionForm.processing" class="w-full sm:w-auto inline-flex justify-center rounded-lg shadow-md px-5 py-2.5 bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 transition transform hover:scale-102">
                                        {{ promotionForm.processing ? 'Ejecutando Promoción...' : 'Iniciar Nuevo Ciclo Lectivo' }}
                                    </button>
                                    <button @click="showPromotionModal = false" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-slate-300 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Confirmar Eliminación Segura -->
                <div v-if="showDeleteModal" class="fixed z-50 inset-0 overflow-y-auto">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity backdrop-blur-sm bg-gray-900/50" @click="showDeleteModal = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                            <form @submit.prevent="submitDeleteUser">
                                <div class="bg-white px-6 pt-6 pb-4">
                                    <div class="flex items-center gap-3 mb-4 text-red-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <h3 class="text-xl font-bold text-gray-900">Eliminar del Sistema</h3>
                                    </div>
                                    <p class="text-sm text-gray-500 mb-4">Esta acción es irreversible y eliminará de forma permanente al usuario y todos sus datos relacionados.</p>
                                    <p class="text-xs font-semibold text-gray-700 mb-2">Para confirmar la eliminación, introduzca SU contraseña de administrador:</p>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <input type="password" v-model="deletePassword" placeholder="Contraseña de Administrador" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                            <p class="text-xs text-red-600 mt-1" v-if="deleteError">{{ deleteError }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse rounded-b-2xl">
                                    <button type="submit" :disabled="deleteFormProcessing" class="w-full inline-flex justify-center rounded-lg shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                                        {{ deleteFormProcessing ? 'Eliminando...' : 'Confirmar Eliminación' }}
                                    </button>
                                    <button @click="showDeleteModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
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
