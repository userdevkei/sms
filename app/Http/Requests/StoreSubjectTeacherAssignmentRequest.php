<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectTeacherAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id'          => ['required', 'string', 'exists:users,id'],
            'learning_area_id' => ['required', 'string', 'exists:learning_areas,id'],
            'stream_id'        => [
                'required', 'string', 'exists:streams,id',
                Rule::unique('subject_teacher_assignments')->where(fn ($q) => $q
                    ->where('learning_area_id', $this->input('learning_area_id'))
                    ->where('academic_year', $this->input('academic_year'))),
            ],
            'academic_year'    => ['required', 'string', 'max:9'],
            'status'           => ['required', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return ['stream_id.unique' => 'This subject already has a teacher assigned for that class and year.'];
    }
}
