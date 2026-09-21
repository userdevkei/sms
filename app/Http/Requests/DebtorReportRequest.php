<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * One request class for screen + both exports, so the filters can't drift apart.
 */
class DebtorReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // access is enforced by route middleware
    }

    public function rules(): array
    {
        return [
            'grade_ids'    => ['nullable', 'array'],
            'grade_ids.*'  => ['string', 'max:32'],
            'stream_ids'   => ['nullable', 'array'],
            'stream_ids.*' => ['string', 'max:32'],
            'min_pct'      => ['nullable', 'numeric', 'between:0,100'],
            'max_pct'      => ['nullable', 'numeric', 'between:0,100'],
            'min_balance'  => ['nullable', 'numeric', 'min:0'],
            'q'            => ['nullable', 'string', 'max:100'],
            'group_by'     => ['nullable', 'in:none,grade,stream'],
        ];
    }

    /** Normalised filters consumed by DebtorReportService. */
    public function filters(): array
    {
        $num = fn (string $k) => $this->filled($k) ? (float) $this->input($k) : null;

        $min = $num('min_pct');
        $max = $num('max_pct');
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min]; // forgive swapped From/To
        }

        return [
            'grade_ids'   => array_values(array_filter((array) $this->input('grade_ids', []))),
            'stream_ids'  => array_values(array_filter((array) $this->input('stream_ids', []))),
            'min_pct'     => $min,
            'max_pct'     => $max,
            'min_balance' => $num('min_balance'),
            'q'           => trim((string) $this->input('q', '')),
            'group_by'    => $this->input('group_by') ?: 'none',
        ];
    }
}
