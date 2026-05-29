<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoletinCalificacion extends Model
{
    protected $table = 'boletines_calificaciones';

    protected $fillable = [
        'registro_internado_id',
        'numero_periodo',
        'ruta_archivo',
        'estado_aprobacion'
    ];

    public function registroInternado()
    {
        return $this->belongsTo(RegistroInternado::class, 'registro_internado_id');
    }
}
