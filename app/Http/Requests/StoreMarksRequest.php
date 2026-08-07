<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('results.enter_marks') || $this->user()?->hasPermission('curriculum.manage');
    }

    public function rules(): array
    {
        $assessment = $this->route('assessment');

        return [
            'results'                       => ['required', 'array'],
            'results.*.enrollment_id'       => ['required', 'string', 'exists:student_enrollments,id'],
            'results.*.score'               => ['nullable', 'numeric', 'min:0', 'max:' . ($assessment->max_score ?? 999999)],
            'results.*.competency_level'    => ['nullable', 'in:EE,ME,AE,BE'],
            'results.*.is_absent'           => ['nullable', 'boolean'],
            'results.*.remarks'             => ['nullable', 'string', 'max:500'],
        ];
    }
}
