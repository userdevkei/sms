<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveRoomReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('accommodation.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'string', 'exists:rooms,id'],
        ];
    }
}
