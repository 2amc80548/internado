<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionSistema extends Model
{
    protected $table = 'configuracion_sistema';

    protected $fillable = [
        'ruta_logo',
        'color_hexadecimal',
        'ruta_qr_pagos',
        'modo_mensualidad_automatica',
        'edicion_perfil_habilitada',
    ];
}
