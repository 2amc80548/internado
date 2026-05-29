<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_gestion' => 'required|string|max:10',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tipo_periodo_academico' => 'required|string|in:Bimestre,Trimestre',
            'cantidad_periodos' => 'required|integer|min:1|max:6',
        ];
    }
}
