<?php

// app/Http/Requests/StoreCommunicationRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommunicationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('communication.send') ?? false; }

    public function rules(): array
    {
        return [
            'communication_template_id' => ['nullable', 'exists:communication_templates,id'],
            'channel'  => ['required', Rule::in(['sms', 'whatsapp', 'email'])],
            'subject'  => ['required_if:channel,email', 'nullable', 'string', 'max:255'],
            'body'     => ['required', 'string'],

            'audience_type' => ['required', Rule::in(['manual', 'students', 'staff', 'guardians'])],

            // manual
            'recipients' => ['required_if:audience_type,manual', 'nullable', 'string'],

            // students
            'students_mode'  => ['required_if:audience_type,students', 'nullable', Rule::in(['all', 'grade', 'specific'])],
            'grade_level_id' => ['required_if:students_mode,grade', 'nullable', 'exists:grade_levels,id'],
            'student_ids'    => ['required_if:students_mode,specific', 'nullable', 'array'],
            'student_ids.*'  => ['integer', 'exists:students,id'],

            // staff
            'staff_mode' => ['required_if:audience_type,staff', 'nullable', Rule::in(['all', 'specific'])],
            'staff_ids'  => ['required_if:staff_mode,specific', 'nullable', 'array'],
            'staff_ids.*' => ['integer', 'exists:users,id'],

            // guardians
            'guardians_mode' => ['required_if:audience_type,guardians', 'nullable', Rule::in(['all', 'specific'])],
            'guardian_ids'   => ['required_if:guardians_mode,specific', 'nullable', 'array'],
            'guardian_ids.*' => ['integer', 'exists:guardians,id'],

            'send_at'         => ['nullable', 'date', 'after:now'],
            'recurrence_rule' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
        ];
    }
}
