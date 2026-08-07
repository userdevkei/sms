<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLearningAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:150', 'unique:learning_areas,name'],
            'code'                => ['nullable', 'string', 'max:20', 'unique:learning_areas,code'],
            'description'         => ['nullable', 'string', 'max:500'],
            'is_compulsory'       => ['nullable', 'boolean'],
            'status'              => ['required', 'in:active,inactive'],
            'grade_levels'        => ['nullable', 'array'],
            'grade_levels.*'      => ['string', 'exists:grade_levels,id'],
        ];
    }
}
