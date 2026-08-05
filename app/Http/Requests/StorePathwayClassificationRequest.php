<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePathwayClassificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('progression.initiate') ?? false;
    }

    public function rules(): array
    {
        return [
            'new_academic_year'                => ['required', 'string', 'max:9'],
            'classifications'                  => ['required', 'array', 'min:1'],
            'classifications.*.enrollment_id'  => ['required', 'string', 'exists:student_enrollments,id'],
            'classifications.*.pathway_id'     => ['required', 'string', 'exists:pathways,id'],
        ];
    }
}
