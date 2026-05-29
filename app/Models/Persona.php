<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Persona extends Model
{
    use HasFactory;
    protected $primaryKey = 'ci';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'ci',
        'nombre',
        'ap_paterno',
        'ap_materno',
        'celular',
        'fecha_nacimiento',
        'sexo',
        'estado',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'persona_ci', 'ci');
    }

    public function tutor()
    {
        return $this->hasOne(Tutor::class, 'persona_ci', 'ci');
    }

    public function estudiante()
    {
        return $this->hasOne(Estudiante::class, 'persona_ci', 'ci');
    }

    public function personal()
    {
        return $this->hasOne(Personal::class, 'persona_ci', 'ci');
    }
}
