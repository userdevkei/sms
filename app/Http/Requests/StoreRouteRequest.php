<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('transport.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'                    => ['required', 'string', 'max:150'],
            'code'                    => ['nullable', 'string', 'max:20', 'unique:transport_routes,code'],
            'description'             => ['nullable', 'string', 'max:500'],
            'status'                  => ['required', 'in:active,inactive'],
            'stops'                   => ['required', 'array', 'min:1'],
            'stops.*.name'            => ['required', 'string', 'max:150'],
            'stops.*.landmark_description' => ['nullable', 'string', 'max:255'],
            'stops.*.fare'            => ['required', 'numeric', 'min:0'],
        ];
    }
}
