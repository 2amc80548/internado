<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoletinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registro_internado_id' => 'required|exists:registros_internado,id',
            'numero_periodo' => 'required|integer|min:1|max:6',
            'ruta_archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'estado_aprobacion' => 'nullable|string',
        ];
    }
}
