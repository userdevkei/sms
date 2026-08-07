<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoteheadRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('fee_structures.manage') ?? false; }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:150', 'unique:voteheads,name'],
            'code'        => ['nullable', 'string', 'max:20', 'unique:voteheads,code'],
            'category'    => ['required', 'in:tuition,activity,remedial,examination,other'],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['required', 'in:active,inactive'],
        ];
    }
}
