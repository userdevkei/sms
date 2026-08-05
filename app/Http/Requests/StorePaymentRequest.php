<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('payments.manage') ?? false; }

    public function rules(): array
    {
        return [
            'invoice_id'       => ['required', 'string', 'exists:invoices,id'],
            'method'           => ['required', 'in:cash,mpesa,bank'],
            'amount'           => ['required', 'numeric', 'min:1'],
            'reference_number' => ['required_unless:method,cash', 'nullable', 'string', 'max:100'],
            'paid_on'          => ['required', 'date', 'before_or_equal:today'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ];
    }
}
