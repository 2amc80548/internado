<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CursoBth extends Model
{
    protected $table = 'cursos_bth';

    protected $fillable = [
        'carrera_tecnica_id',
        'nivel'
    ];

    public function carreraTecnica()
    {
        return $this->belongsTo(CarreraTecnica::class, 'carrera_tecnica_id');
    }

    public function registrosInternado()
    {
        return $this->hasMany(RegistroInternado::class, 'curso_bth_id');
    }
}
