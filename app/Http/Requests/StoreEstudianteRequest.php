<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'persona_ci' => 'required|string|exists:personas,ci',
            'tutor_id' => 'nullable|exists:tutores,id',
            'comunidad_id' => 'nullable|exists:comunidades,id',
            'colegio_origen' => 'nullable|string|max:255',
            'motivo_ingreso' => 'nullable|string',
            'enfermedad_base' => 'nullable|string',
            'estado_global' => 'nullable|string|in:Activo,Retirado,Bachiller,Graduado BTH',
            'año_egreso_bachiller' => 'nullable|integer',
            'año_egreso_bth' => 'nullable|integer',
        ];
    }
}
