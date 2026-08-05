<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGradeLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'education_level_id' => ['required', 'string', 'exists:education_levels,id'],
            'name'                => ['required', 'string', 'max:100', 'unique:grade_levels,name'],
            'code'                => ['nullable', 'string', 'max:10'],
            'sequence'            => ['required', 'integer', 'min:1', 'unique:grade_levels,sequence'],
            'status'              => ['required', 'in:active,inactive'],
        ];
    }
}
