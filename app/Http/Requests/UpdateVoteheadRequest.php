<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVoteheadRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('fee_structures.manage') ?? false; }

    public function rules(): array
    {
        $id = $this->route('votehead')->id;

        return [
            'name'        => ['required', 'string', 'max:150', Rule::unique('voteheads', 'name')->ignore($id)],
            'code'        => ['nullable', 'string', 'max:20', Rule::unique('voteheads', 'code')->ignore($id)],
            'category'    => ['required', 'in:tuition,activity,remedial,examination,other'],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['required', 'in:active,inactive'],
        ];
    }
}
