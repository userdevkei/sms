<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWhatsappGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'provider'            => ['required', Rule::in(['whatsapp_cloud', 'twilio', 'custom'])],

            // whatsapp_cloud
            'phone_number_id'     => ['required_if:provider,whatsapp_cloud', 'nullable', 'string'],
            'business_account_id'=> ['nullable', 'string'],
            'access_token'        => ['nullable', 'string'], // blank on edit = keep existing

            // twilio (sandbox or a real WhatsApp-enabled Twilio number)
            'account_sid'         => ['required_if:provider,twilio', 'nullable', 'string'],
            'auth_token'          => ['nullable', 'string'],
            'from'                => ['required_if:provider,twilio', 'nullable', 'string'], // e.g. +14155238886, no "whatsapp:" prefix

            // shared
            'verify_token'        => ['nullable', 'string'],

            // custom
            'endpoint_url'        => ['required_if:provider,custom', 'nullable', 'url'],
            'api_key'             => ['nullable', 'string'],
        ];
    }
}
