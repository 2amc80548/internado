<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gestion extends Model
{
    protected $table = 'gestiones';

    protected $fillable = [
        'nombre_gestion',
        'fecha_inicio',
        'fecha_fin',
        'tipo_periodo_academico',
        'cantidad_periodos',
        'estado'
    ];

    public function registrosInternado()
    {
        return $this->hasMany(RegistroInternado::class, 'gestion_id');
    }
}
