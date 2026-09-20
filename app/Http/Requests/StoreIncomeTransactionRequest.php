<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomeTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('income.create') || $this->user()?->hasPermission('income.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'reference'              => ['nullable', 'string', 'max:100'],
            'transaction_date'       => ['required', 'date'],
            'academic_year'          => ['required', 'string', 'max:9'],
            'term'                   => ['required', 'integer', 'in:1,2,3'],
            'received_from'          => ['nullable', 'string', 'max:150'],
            'payment_method'         => ['nullable', 'in:cash,mpesa,bank,cheque'],
            'description'            => ['nullable', 'string', 'max:500'],

            'items'                  => ['required', 'array', 'min:1'],
            'items.*.category_id'    => ['required', 'string', 'exists:income_categories,id'],
            'items.*.description'    => ['required', 'string', 'max:200'],
            'items.*.quantity'       => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
        ];
    }
}
