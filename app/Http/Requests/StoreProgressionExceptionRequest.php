<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgressionExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('progression.initiate') ?? false;
    }

    public function rules(): array
    {
        return [
            'enrollment_id'     => ['required', 'string', 'exists:student_enrollments,id'],
            'type'              => ['required', 'in:repeat,transferred_out,withdrawn,deceased'],
            'reason'            => ['required', 'string', 'max:1000'],
            'new_academic_year' => ['required_if:type,repeat', 'nullable', 'string', 'max:9'],
        ];
    }
}
