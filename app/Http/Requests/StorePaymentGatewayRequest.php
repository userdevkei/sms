<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.manage') ?? false;
    }

    public function rules(): array
    {
        $secretRule = $this->isMethod('PATCH') ? 'nullable' : 'required';

        $rules = [
            'provider' => ['required', 'in:mpesa,bank_api'],
            'name'     => ['required', 'string', 'max:150'],
        ];

        if ($this->input('provider') === 'mpesa') {
            $rules['environment']     = ['required', 'in:sandbox,live'];
            $rules['consumer_key']    = [$secretRule, 'string', 'max:500'];
            $rules['consumer_secret'] = [$secretRule, 'string', 'max:500'];
            $rules['shortcode']       = ['required', 'string', 'max:20'];
            $rules['passkey']         = [$secretRule, 'string', 'max:500'];
            $rules['callback_url']    = ['required', 'url', 'max:500'];
        } else {
            $rules['bank_name']      = ['required', 'string', 'max:150'];
            $rules['api_key']        = [$secretRule, 'string', 'max:500'];
            $rules['api_secret']     = ['nullable', 'string', 'max:500'];
            $rules['account_number'] = ['required', 'string', 'max:50'];
            $rules['endpoint_url']   = ['required', 'url', 'max:500'];
        }

        return $rules;
    }
}
