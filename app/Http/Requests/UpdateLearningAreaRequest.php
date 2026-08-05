<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLearningAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.manage') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('learning_area')->id;

        return [
            'name'                => ['required', 'string', 'max:150', Rule::unique('learning_areas', 'name')->ignore($id)],
            'code'                => ['nullable', 'string', 'max:20', Rule::unique('learning_areas', 'code')->ignore($id)],
            'description'         => ['nullable', 'string', 'max:500'],
            'is_compulsory'       => ['nullable', 'boolean'],
            'status'              => ['required', 'in:active,inactive'],
            'grade_levels'        => ['nullable', 'array'],
            'grade_levels.*'      => ['string', 'exists:grade_levels,id'],
        ];
    }
}
