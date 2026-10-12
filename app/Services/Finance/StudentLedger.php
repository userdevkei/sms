<?php

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentLedger
{
    /**
     * @return array{0: Collection, 1: array} [ledger rows with running balance, totals]
     */
    public function for(User $student): array
    {
        $invoices = Invoice::where('user_id', $student->id)
            ->with('items')
            ->orderBy('academic_year')
            ->orderBy('term')
            ->get();

        $payments = Payment::where('user_id', $student->id)->get();

        $entries = collect();

        foreach ($invoices as $invoice) {
            $entries->push([
                'date'        => $invoice->created_at->toDateString(),
                'ts'          => $invoice->created_at->timestamp,
                'order'       => 0, // invoice before receipt when timestamps tie
                'type'        => 'invoice',
                'label'       => 'Invoice ' . $invoice->invoice_number,
                'description' => 'Term ' . $invoice->term . ' ' . $invoice->academic_year . ' fees',
                'debit'       => $invoice->total_amount,
                'credit'      => null,
                'invoice'     => $invoice,
            ]);
        }

        foreach ($payments as $payment) {
            $entries->push([
                'date'        => $payment->paid_on->toDateString(),
                'ts'          => $payment->paid_on->timestamp,
                'order'       => 1,
                'type'        => 'payment',
                'label'       => 'Receipt ' . $payment->payment_number,
                'description' => strtoupper($payment->method) . ($payment->reference_number ? ' · ' . $payment->reference_number : ''),
                'debit'       => null,
                'credit'      => $payment->amount,
                'invoice'     => null,
            ]);
        }

        $balance = 0;
        $ledger = $entries
            ->sortBy([['ts', 'asc'], ['order', 'asc']])
            ->values()
            ->map(function ($row) use (&$balance) {
                $balance += ($row['debit'] ?? 0) - ($row['credit'] ?? 0);
                $row['balance'] = $balance;

                return $row;
            });

        $totals = [
            'total_charged' => $invoices->sum('total_amount'),
            'total_paid'    => $payments->sum('amount'),
            'balance'       => $balance,
        ];

        return [$ledger, $totals];
    }
}
