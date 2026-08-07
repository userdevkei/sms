<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('students.manage') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('student')->id;

        return [
            'userID'        => ['required', 'string', 'max:50', Rule::unique('users', 'userID')->ignore($userId)],
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
            'email'         => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'phone_number'  => ['nullable', 'string', 'max:100', Rule::unique('users', 'phone_number')->ignore($userId)],
            'password'      => ['nullable', 'string', 'min:8', 'confirmed'],
            'status'        => ['required', 'in:pending,active,inactive,suspended,transferred,graduated,deceased,terminated'],
            'avatar'        => ['nullable', 'image', 'max:2048'],
        ];
    }
}
