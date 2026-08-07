<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:150'],
            'host'         => ['required', 'string', 'max:255'],
            'port'         => ['required', 'integer', 'min:1', 'max:65535'],
            'username'     => ['required', 'string', 'max:255'],
            'password'     => [$this->isMethod('PATCH') ? 'nullable' : 'required', 'string', 'max:500'],
            'encryption'   => ['required', 'in:tls,ssl,none'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name'    => ['required', 'string', 'max:150'],
        ];
    }
}
