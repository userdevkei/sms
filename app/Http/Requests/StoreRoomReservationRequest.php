<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('accommodation.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id'            => ['required', 'string', 'exists:users,id'],
            'hostel_id'          => ['required', 'string', 'exists:hostels,id'],
            'preferred_room_id'  => ['nullable', 'string', 'exists:rooms,id'],
            'academic_year'      => ['required', 'string', 'max:9'],
            'term'               => ['nullable', 'string', 'max:50'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ];
    }
}
