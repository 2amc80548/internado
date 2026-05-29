<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensualidad extends Model
{
    protected $table = 'mensualidades';

    protected $fillable = [
        'registro_internado_id',
        'mes',
        'monto',
        'fecha_pago',
        'total',
        'estado',
        'metodo_pago',
        'ruta_comprobante_qr',
        'motivo_anulacion'
    ];

    public function registroInternado()
    {
        return $this->belongsTo(RegistroInternado::class, 'registro_internado_id');
    }
}
