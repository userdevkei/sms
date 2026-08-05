<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEducationLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100', 'unique:education_levels,name'],
            'code'        => ['required', 'string', 'max:10', 'unique:education_levels,code'],
            'sequence'    => ['required', 'integer', 'min:1', 'unique:education_levels,sequence'],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['required', 'in:active,inactive'],
        ];
    }
}
