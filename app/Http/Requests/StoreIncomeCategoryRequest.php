<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncomeCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('accounting.manage') ?? false;
    }

    public function rules(): array
    {
        $categoryId = $this->route('income_category')?->id;

        return [
            'name'        => ['required', 'string', 'max:150', Rule::unique('income_categories', 'name')->ignore($categoryId)],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['required', 'in:active,inactive'],
        ];
    }
}
