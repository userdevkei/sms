<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('accommodation.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id'        => ['required', 'string', 'exists:users,id'],
            'room_id'        => ['required', 'string', 'exists:rooms,id'],
            'academic_year'  => ['required', 'string', 'max:9'],
            'term'           => ['nullable', 'string', 'max:50'],
            'allocated_on'   => ['nullable', 'date'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ];
    }
}
