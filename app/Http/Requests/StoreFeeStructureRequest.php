<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeeStructureRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('fee_structures.manage') ?? false; }

    public function rules(): array
    {
        return [
            'grade_level_ids'     => ['required', 'array', 'min:1'],
            'grade_level_ids.*'   => ['required', 'string', 'exists:grade_levels,id', 'distinct'],
            'notes'               => ['nullable', 'string', 'max:500'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.votehead_id' => ['required', 'string', 'exists:voteheads,id', 'distinct'],
            'items.*.amount'      => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'grade_level_ids.required' => 'Select at least one grade level.',
        ];
    }
}
