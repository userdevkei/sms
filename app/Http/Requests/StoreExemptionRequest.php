<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExemptionRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('exemptions.manage') ?? false; }

    public function rules(): array
    {
        return [
            'user_id'       => ['required', 'string', 'exists:users,id'],
            'votehead_id'   => ['nullable', 'string', 'exists:voteheads,id'],
            'type'          => ['required', 'in:percentage,fixed'],
            'value'         => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'academic_year' => ['required', 'string', 'max:9'],
            'term'          => ['required', 'integer', 'in:1,2,3'],
            'reason'        => ['required', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($this->input('type') === 'percentage' && (float) $this->input('value') > 100) {
                $v->errors()->add('value', 'A percentage exemption cannot exceed 100%.');
            }
        });
    }
}
