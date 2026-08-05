<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'grade_level_id' => ['required', 'string', 'exists:grade_levels,id'],
            'stream_id'      => ['nullable', 'string', 'exists:streams,id'],
            'academic_year'  => ['required', 'string', 'max:9'],
            'enrolled_on'    => ['nullable', 'date'],
        ];
    }
}
