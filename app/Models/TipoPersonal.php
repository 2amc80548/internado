<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPersonal extends Model
{
    protected $table = 'tipos_personal';

    protected $fillable = ['nombre'];

    public function personales()
    {
        return $this->hasMany(Personal::class, 'tipo_personal_id');
    }
}
