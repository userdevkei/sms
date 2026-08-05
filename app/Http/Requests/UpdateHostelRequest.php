<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHostelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('accommodation.manage') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('hostel')->id;

        return [
            'name'                  => ['required', 'string', 'max:150', Rule::unique('hostels', 'name')->ignore($id)],
            'gender'                => ['required', 'in:male,female,mixed'],
            'warden_id'             => ['nullable', 'string', 'exists:users,id'],
            'default_fee_per_term'  => ['nullable', 'numeric', 'min:0'],
            'description'           => ['nullable', 'string', 'max:500'],
            'status'                => ['required', 'in:active,inactive'],
        ];
    }
}
