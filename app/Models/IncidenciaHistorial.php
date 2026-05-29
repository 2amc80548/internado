<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidenciaHistorial extends Model
{
    protected $table = 'incidencias_historial';

    protected $fillable = [
        'registro_internado_id',
        'fecha',
        'tipo_falta',
        'descripcion',
        'sancion',
        'estado_sancion'
    ];

    public function registroInternado()
    {
        return $this->belongsTo(RegistroInternado::class, 'registro_internado_id');
    }
}
