<?php

namespace App\Http\Requests;

use App\Models\AssessmentType;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('results.enter_marks') || $this->user()?->hasPermission('curriculum.manage');
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:150'],
            'learning_area_id'      => ['required', 'array', 'min:1'],
            'learning_area_id.*'    => ['string', 'exists:learning_areas,id'],
            'stream_id'             => ['required', 'array', 'min:1'],
            'stream_id.*'           => ['string', 'exists:streams,id'],
            'academic_term_id'      => ['required', 'string', 'exists:academic_terms,id'],
            'assessment_type_id'    => ['required', 'string', 'exists:assessment_types,id'],
            'max_score'             => ['nullable', 'numeric', 'min:1'],
            'assessment_date'       => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = AssessmentType::query()->find($this->input('assessment_type_id'));

            if ($type && $type->scoring_mode === 'score' && empty($this->input('max_score'))) {
                $validator->errors()->add('max_score', 'Max score is required for this assessment type.');
            }
        });
    }
}
