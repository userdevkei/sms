<?php

// app/Http/Requests/StoreCommunicationTemplateRequest.php
namespace App\Http\Requests;

use App\Models\CommunicationTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommunicationTemplateRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('communication.templates.manage') ?? false; }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:150'],
            'trigger_key'   => ['nullable', Rule::in(array_keys(CommunicationTemplate::TRIGGER_KEYS))],
            'channels'      => ['required', 'array', 'min:1'],
            'channels.*'    => [Rule::in(['sms', 'email', 'whatsapp'])],
            'subject'       => ['required_if:channels.*,email', 'nullable', 'string', 'max:255'],
            'sms_body'      => ['nullable', 'string', 'max:1000'],
            'email_body'    => ['nullable', 'string'],
            'whatsapp_body' => ['nullable', 'string', 'max:1000'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }
}
