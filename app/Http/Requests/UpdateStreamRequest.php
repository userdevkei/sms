<?php

namespace App\Http\Requests;

use App\Models\GradeLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateStreamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.manage') ?? false;
    }

    public function rules(): array
    {
        $streamId = $this->route('stream')->id;

        return [
            'grade_level_id'   => ['required', 'string', 'exists:grade_levels,id'],
            'pathway_id'       => ['nullable', 'string', 'exists:pathways,id'],
            'name'             => [
                'required', 'string', 'max:100',
                Rule::unique('streams', 'name')
                    ->where(fn ($q) => $q->where('grade_level_id', $this->input('grade_level_id')))
                    ->ignore($streamId),
            ],
            'capacity'         => ['nullable', 'integer', 'min:1', 'max:200'],
            'class_teacher_id' => ['nullable', 'string', 'exists:users,id'],
            'status'           => ['required', 'in:active,inactive'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $gradeLevel = GradeLevel::query()->find($this->input('grade_level_id'));

            if (! $gradeLevel) {
                return;
            }

            $isSenior = $gradeLevel->isSeniorSecondary();

            if ($isSenior && empty($this->input('pathway_id'))) {
                $validator->errors()->add('pathway_id', 'A pathway is required for Senior Secondary streams.');
            }

            if (! $isSenior && ! empty($this->input('pathway_id'))) {
                $validator->errors()->add('pathway_id', 'Pathways only apply to Senior Secondary grade levels.');
            }
        });
    }
}
