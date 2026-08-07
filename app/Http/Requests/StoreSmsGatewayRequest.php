<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSmsGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.manage') ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'provider' => ['required', 'in:africas_talking,custom'],
            'name'     => ['required', 'string', 'max:150'],
        ];

        if ($this->input('provider') === 'africas_talking') {
            $rules['username']  = ['required', 'string', 'max:150'];
            $rules['api_key']   = [$this->isMethod('PATCH') ? 'nullable' : 'required', 'string', 'max:500'];
            $rules['sender_id'] = ['nullable', 'string', 'max:50'];
        } else {
            $rules['endpoint_url'] = ['required', 'url', 'max:500'];
            $rules['api_key']      = [$this->isMethod('PATCH') ? 'nullable' : 'required', 'string', 'max:500'];
        }

        return $rules;
    }
}
