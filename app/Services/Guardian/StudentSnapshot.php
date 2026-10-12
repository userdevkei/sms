<?php

namespace App\Services\Guardian;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StudentEnrollment;
use Carbon\Carbon;

class StudentSnapshot
{
    // ── ASSUMPTIONS: change these if your columns differ ──
    public const INVOICE_DUE    = 'due_date';
    public const PAYMENT_DATE   = 'paid_on';
    public const PAYMENT_AMOUNT = 'amount';

    /**
     * Per-student summary keyed by student id.
     * balance > 0 = owes, < 0 = credit (invoiced minus paid).
     */
    public function for(iterable $studentIds): array
    {
        $ids = collect($studentIds)->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $invoiced = Invoice::whereIn('user_id', $ids)->groupBy('user_id')
            ->selectRaw('user_id, SUM(total_amount) as total')->pluck('total', 'user_id');

        $paid = Payment::whereIn('user_id', $ids)->groupBy('user_id')
            ->selectRaw('user_id, SUM(' . self::PAYMENT_AMOUNT . ') as total')->pluck('total', 'user_id');

        $nextDue = Invoice::whereIn('user_id', $ids)->where('balance', '>', 0)->whereNotNull(self::INVOICE_DUE)
            ->groupBy('user_id')
            ->selectRaw('user_id, MIN(' . self::INVOICE_DUE . ') as due')->pluck('due', 'user_id');

        $enrollments = StudentEnrollment::with(['gradeLevel', 'stream'])
            ->whereIn('user_id', $ids)->where('status', 'active')->get()->keyBy('user_id');

        $out = [];
        foreach ($ids as $id) {
            $inv = (float) ($invoiced[$id] ?? 0);
            $pay = (float) ($paid[$id] ?? 0);
            $due = isset($nextDue[$id]) ? Carbon::parse($nextDue[$id]) : null;

            $out[$id] = [
                'invoiced'     => $inv,
                'paid'         => $pay,
                'balance'      => round($inv - $pay, 2),
                'next_due'     => $due,
                'overdue'      => $due && $due->isPast() && ! $due->isToday(),
                'last_payment' => Payment::where('user_id', $id)->orderByDesc(self::PAYMENT_DATE)->first(),
                'enrollment'   => $enrollments[$id] ?? null,
            ];
        }

        return $out;
    }

    public static function paidOn($payment): ?Carbon
    {
        return $payment && $payment->{self::PAYMENT_DATE} ? Carbon::parse($payment->{self::PAYMENT_DATE}) : null;
    }

    public static function paidAmount($payment): float
    {
        return (float) ($payment->{self::PAYMENT_AMOUNT} ?? 0);
    }

    public static function dueOn($invoice): ?Carbon
    {
        return $invoice && $invoice->{self::INVOICE_DUE} ? Carbon::parse($invoice->{self::INVOICE_DUE}) : null;
    }
}
