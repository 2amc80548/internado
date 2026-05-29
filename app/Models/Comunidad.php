<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comunidad extends Model
{
    protected $table = 'comunidades';

    protected $fillable = ['provincia_id', 'nombre'];

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class, 'comunidad_id');
    }
}
