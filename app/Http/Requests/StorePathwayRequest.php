<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePathwayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:150', 'unique:pathways,name'],
            'code'             => ['nullable', 'string', 'max:20', 'unique:pathways,code'],
            'description'      => ['nullable', 'string', 'max:500'],
            'status'           => ['required', 'in:active,inactive'],
            'learning_areas'   => ['nullable', 'array'],
            'learning_areas.*' => ['string', 'exists:learning_areas,id'],
        ];
    }
}
