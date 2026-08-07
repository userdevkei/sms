<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('transport.manage') ?? false;
    }

    public function rules(): array
    {
//        $routeId = $this->route('transport_routes')->id;
        $routeId = $this->transportRoute->id;


        return [
            'name'                          => ['required', 'string', 'max:150'],
            'code'                          => ['nullable', 'string', 'max:20', Rule::unique('transport_routes', 'code')->ignore($routeId)],
            'description'                   => ['nullable', 'string', 'max:500'],
            'status'                        => ['required', 'in:active,inactive'],
            'stops'                         => ['required', 'array', 'min:1'],
            'stops.*.id'                    => ['nullable', 'string'], // existing stop ids, blank = new stop
            'stops.*.name'                  => ['required', 'string', 'max:150'],
            'stops.*.landmark_description'  => ['nullable', 'string', 'max:255'],
            'stops.*.fare'                  => ['required', 'numeric', 'min:0'],
        ];
    }
}
