<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    protected $table = 'personal';

    protected $fillable = [
        'persona_ci',
        'tipo_personal_id',
        'profesion'
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_ci', 'ci');
    }

    public function tipoPersonal()
    {
        return $this->belongsTo(TipoPersonal::class, 'tipo_personal_id');
    }
}
