<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
    protected $fillable = ['nombre'];

    public function comunidades()
    {
        return $this->hasMany(Comunidad::class, 'provincia_id');
    }
}
