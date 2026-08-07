<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEducationLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.manage') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('education_level')->id;

        return [
            'name'        => ['required', 'string', 'max:100', Rule::unique('education_levels', 'name')->ignore($id)],
            'code'        => ['required', 'string', 'max:10', Rule::unique('education_levels', 'code')->ignore($id)],
            'sequence'    => ['required', 'integer', 'min:1', Rule::unique('education_levels', 'sequence')->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['required', 'in:active,inactive'],
        ];
    }
}
