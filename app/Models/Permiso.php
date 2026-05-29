<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $fillable = ['nombre'];

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'permiso_id', 'rol_id')->withTimestamps();
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'usuario_permiso', 'permiso_id', 'user_id')->withTimestamps();
    }
}
