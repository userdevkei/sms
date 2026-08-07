<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('transport.manage') ?? false;
    }

    public function rules(): array
    {
        $driverId = $this->route('driver')->id;

        return [
            // user_id intentionally not editable here — see controller note below
            'license_number'  => ['required', 'string', 'max:50', Rule::unique('drivers', 'license_number')->ignore($driverId)],
            'license_class'   => ['nullable', 'string', 'max:20'],
            'license_expiry'  => ['nullable', 'date'],
            'status'          => ['required', 'in:active,inactive'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ];
    }
}
