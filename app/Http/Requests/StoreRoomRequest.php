<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('accommodation.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'         => [
                'required', 'string', 'max:100',
                Rule::unique('rooms', 'name')->where(fn ($q) => $q->where('hostel_id', $this->route('hostel')->id)),
            ],
            'capacity'     => ['required', 'integer', 'min:1', 'max:50'],
            'fee_per_term' => ['nullable', 'numeric', 'min:0'],
            'status'       => ['required', 'in:active,inactive'],
        ];
    }
}
