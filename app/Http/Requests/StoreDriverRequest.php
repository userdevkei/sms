<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('transport.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id'         => ['required', 'string', 'exists:users,id', 'unique:drivers,user_id'],
            'license_number'  => ['required', 'string', 'max:50', 'unique:drivers,license_number'],
            'license_class'   => ['nullable', 'string', 'max:20'],
            'license_expiry'  => ['nullable', 'date'],
            'status'          => ['required', 'in:active,inactive'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.unique' => 'This user already has a driver record.',
        ];
    }
}
