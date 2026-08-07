<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradeLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.manage') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('grade_level')->id;

        return [
            'education_level_id' => ['required', 'string', 'exists:education_levels,id'],
            'name'                => ['required', 'string', 'max:100', Rule::unique('grade_levels', 'name')->ignore($id)],
            'code'                => ['nullable', 'string', 'max:10'],
            'sequence'            => ['required', 'integer', 'min:1', Rule::unique('grade_levels', 'sequence')->ignore($id)],
            'status'              => ['required', 'in:active,inactive'],
        ];
    }
}
