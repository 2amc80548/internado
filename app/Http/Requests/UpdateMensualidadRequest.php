<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMensualidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registro_internado_id' => 'required|exists:registros_internado,id',
            'mes' => 'required|string|max:20',
            'monto' => 'required|numeric|min:0',
            'fecha_pago' => 'nullable|date',
            'total' => 'required|numeric|min:0',
            'estado' => 'nullable|string|in:Pendiente,Pendiente_Verificacion,Pagado',
            'metodo_pago' => 'nullable|string|in:Efectivo,QR',
            'ruta_comprobante_qr' => 'nullable|image|max:2048',
        ];
    }
}
