<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Students don't get a profile edit form — enforced here too, not
        // just by hiding the link, in case someone hits the edit route directly.
        return ! $this->user()?->hasRole('student');
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'first_name'    => ['required', 'string', 'max:100'],
            'middle_name'   => ['nullable', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'gender'        => ['nullable', 'in:male,female'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'email'         => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone_number'  => ['nullable', 'string', 'max:20'],
            'county'        => ['nullable', 'string', 'max:100'],
            'sub_county'    => ['nullable', 'string', 'max:100'],
            'ward'          => ['nullable', 'string', 'max:100'],
            'avatar'        => ['nullable', 'image', 'max:2048'],
        ];
    }
}
