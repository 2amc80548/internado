<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarreraTecnica extends Model
{
    protected $table = 'carreras_tecnicas';

    protected $fillable = ['nombre'];

    public function cursosBth()
    {
        return $this->hasMany(CursoBth::class, 'carrera_tecnica_id');
    }
}
