<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        \Log::info('authorized');
        return $this->user()?->hasPermission('settings.manage') ?? false;
    }

    public function rules(): array
    {
        \Log::info('rules');
        $secretRule = $this->isMethod('PATCH') ? 'nullable' : 'required';

        \Log::info($secretRule);

        $rules = [
            'provider'    => ['required', 'in:mpesa,equity,kcb,coop'],
            'name'        => ['required', 'string', 'max:150'],
            'environment' => ['required', 'in:sandbox,live'],
        ];

        \Log::info($rules);

        switch ($this->input('provider')) {
            case 'mpesa':
                $rules['consumer_key']    = [$secretRule, 'string', 'max:500'];
                $rules['consumer_secret'] = [$secretRule, 'string', 'max:500'];
                $rules['shortcode']       = ['required', 'string', 'max:20'];
                $rules['passkey']         = [$secretRule, 'string', 'max:500'];
                break;

            case 'equity':
                $rules['account_number'] = ['required', 'string', 'max:50'];
                $rules['ipn_username']   = ['required', 'string', 'max:150'];
                $rules['ipn_password']   = [$secretRule, 'string', 'max:500'];
                break;

            case 'kcb':
                $rules['account_number']  = ['required', 'string', 'max:50'];
                $rules['kcb_public_key']  = [$secretRule, 'string']; // PEM block — no max, can run long
                $rules['consumer_key']    = ['nullable', 'string', 'max:500']; // optional, only for outbound Buni calls
                $rules['consumer_secret'] = ['nullable', 'string', 'max:500'];
                break;

            case 'coop':
                $rules['account_number'] = ['required', 'string', 'max:50'];
                $rules['api_key']        = [$secretRule, 'string', 'max:500'];
                $rules['ipn_key']        = ['nullable', 'string', 'max:500']; // unconfirmed field — keep optional until Co-op's spec is nailed down
                break;
        }

        return $rules;
    }
}
