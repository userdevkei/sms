<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('progression.initiate') ?? false;
    }

    public function rules(): array
    {
        return ['new_academic_year' => ['required', 'string', 'max:9']];
    }
}
