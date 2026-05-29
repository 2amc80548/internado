<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    protected $table = 'tutores';

    protected $fillable = ['persona_ci'];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_ci', 'ci');
    }

    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class, 'tutor_id');
    }
}
