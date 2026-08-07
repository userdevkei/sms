<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePathwayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.manage') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('pathway')->id;

        return [
            'name'             => ['required', 'string', 'max:150', Rule::unique('pathways', 'name')->ignore($id)],
            'code'             => ['nullable', 'string', 'max:20', Rule::unique('pathways', 'code')->ignore($id)],
            'description'      => ['nullable', 'string', 'max:500'],
            'status'           => ['required', 'in:active,inactive'],
            'learning_areas'   => ['nullable', 'array'],
            'learning_areas.*' => ['string', 'exists:learning_areas,id'],
        ];
    }
}
