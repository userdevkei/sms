<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('transport.manage') ?? false;
    }

    public function rules(): array
    {
        \Log::info('we got here');
        return [
            'route_id'   => ['required', 'string', 'exists:transport_routes,id'],
            'vehicle_id' => ['required', 'string', 'exists:vehicles,id'],
            'driver_id'  => ['required', 'string', 'exists:drivers,user_id'],
            'term'       => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
//            'status'     => ['required', 'in:active,ended'],
        ];
    }
}
