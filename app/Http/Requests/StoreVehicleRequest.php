<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('transport.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:20', 'unique:vehicles,registration_number'],
            'make'                 => ['nullable', 'string', 'max:100'],
            'model'                => ['nullable', 'string', 'max:100'],
            'year_of_manufacture'  => ['nullable', 'digits:4', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
            'capacity'             => ['required', 'integer', 'min:1', 'max:200'],
            'color'                => ['nullable', 'string', 'max:50'],
            'logbook_number'       => ['nullable', 'string', 'max:100'],
            'insurance_expiry'     => ['nullable', 'date'],
            'inspection_expiry'    => ['nullable', 'date'],
            'status'               => ['required', 'in:active,under_maintenance,inactive'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ];
    }
}
