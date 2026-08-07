<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.manage') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'userID'        => ['nullable', 'string', 'max:50', Rule::unique('users', 'userID')->ignore($userId)],
            'first_name'    => ['required', 'string', 'max:100'],
            'middle_name'   => ['nullable', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'gender'        => ['nullable', 'in:male,female'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'citizenship'   => ['nullable', 'string', 'max:100'],
            'county'     => ['nullable', 'string', 'max:100', Rule::in(array_keys(config('counties')))],
            'sub_county' => ['nullable', 'string', 'max:100'],
            'ward'       => ['nullable', 'string', 'max:100'],
            'ethnicity' => ['nullable', 'string', Rule::in(config('ethnicities'))],
            'email'         => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'phone_number'  => ['nullable', 'string', 'max:100', Rule::unique('users', 'phone_number')->ignore($userId)],
            'password'      => ['nullable', 'string', 'min:8', 'confirmed'],
            'status'        => ['required', 'in:pending,active,suspended,transferred,graduated,deceased,deferred,terminated'],
            'avatar'        => ['nullable', 'image', 'max:2048'],
            'roles'         => ['nullable', 'array'],
            'roles.*'       => ['string', 'exists:roles,id'],
        ];
    }
}
