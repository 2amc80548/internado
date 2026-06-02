<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroInternado extends Model
{
    protected $table = 'registros_internado';

    protected $fillable = [
        'estudiante_id',
        'gestion_id',
        'curso_id',
        'curso_bth_id',
        'pabellon',
        'cama',
        'observacion',
        'estado_anual',
        'motivo_retiro'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function gestion()
    {
        return $this->belongsTo(Gestion::class, 'gestion_id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function cursoBth()
    {
        return $this->belongsTo(CursoBth::class, 'curso_bth_id');
    }

    public function boletines()
    {
        return $this->hasMany(BoletinCalificacion::class, 'registro_internado_id');
    }

    public function incidencias()
    {
        return $this->hasMany(IncidenciaHistorial::class, 'registro_internado_id');
    }

    public function mensualidades()
    {
        return $this->hasMany(Mensualidad::class, 'registro_internado_id');
    }
}
