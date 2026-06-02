<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Estudiante extends Model
{
    use HasFactory;
    protected $fillable = [
        'persona_ci',
        'tutor_id',
        'comunidad_id',
        'colegio_origen',
        'motivo_ingreso',
        'enfermedad_base',
        'estado_global',
        'año_egreso_bachiller',
        'año_egreso_bth',
        'ruta_foto',
        'edicion_habilitada_hasta',
        'motivo_retiro',
        'motivo_anulacion'
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_ci', 'ci');
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class, 'tutor_id');
    }

    public function comunidad()
    {
        return $this->belongsTo(Comunidad::class, 'comunidad_id');
    }

    public function registrosInternado()
    {
        return $this->hasMany(RegistroInternado::class, 'estudiante_id');
    }
}
