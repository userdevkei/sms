<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class StoreOtherChargeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('other_charges.manage') ?? false; }

    public function rules(): array
    {
        return [
            'other_charge_type_id' => ['required', 'string', 'exists:other_charge_types,id'],
            'description'           => ['required', 'string', 'max:255'],
            'amount'                => ['required', 'numeric', 'min:0'],
            'academic_year'         => ['required', 'string', 'max:9'],
            'term'                  => ['required', 'integer', 'in:1,2,3'],
            'scope'                 => ['required', 'in:student,stream,grade_level'],
            'user_id'               => ['required_if:scope,student', 'nullable', 'string', 'exists:users,id'],
            'stream_id'             => ['required_if:scope,stream', 'nullable', 'string', 'exists:streams,id'],
            'grade_level_id'        => ['required_if:scope,grade_level', 'nullable', 'string', 'exists:grade_levels,id'],
        ];
    }
}
