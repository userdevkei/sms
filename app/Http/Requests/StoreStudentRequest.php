<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('students.manage') ?? false;
        // NOTE: uses a students.* permission, not users.manage — see point 6 below on why.
    }

    public function rules(): array
    {
        return [
            'userID'        => ['required', 'string', 'max:50', 'unique:users,userID'], // required — was nullable for general Users
            'first_name'    => ['required', 'string', 'max:100'],
            'middle_name'   => ['nullable', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'gender'        => ['nullable', 'in:male,female'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'citizenship'   => ['nullable', 'string', 'max:100'],
            'ethnicity'     => ['nullable', 'string', 'in:' . implode(',', config('ethnicities'))],
            'county'        => ['nullable', 'string', 'max:100'],
            'sub_county'    => ['nullable', 'string', 'max:100'],
            'ward'          => ['nullable', 'string', 'max:100'],
            'email'         => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone_number'  => ['nullable', 'string', 'max:100', 'unique:users,phone_number'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
            'status'        => ['required', 'in:pending,active,inactive,suspended,transferred,graduated,deceased,terminated'],
            'avatar'        => ['nullable', 'image', 'max:2048'],
        ];
    }
}
