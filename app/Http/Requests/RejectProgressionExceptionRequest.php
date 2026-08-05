<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectProgressionExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('progression.approve') ?? false;
    }

    public function rules(): array
    {
        return ['review_notes' => ['nullable', 'string', 'max:1000']];
    }
}
