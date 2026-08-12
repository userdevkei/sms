<?php
namespace App\Services\Banking;

use App\DataTransferObjects\BankTransactionData;
use App\Models\BankTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class BankPaymentReconciliationService
{
    public function process(BankTransactionData $data): BankTransaction
    {
        // Idempotency — bank retries IPN until it gets a 200
        $existing = BankTransaction::where('bank', $data->bank)
            ->where('transaction_ref', $data->transactionRef)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($data) {
            $txn = BankTransaction::create([
                'bank' => $data->bank,
                'transaction_ref' => $data->transactionRef,
                'account_reference' => $data->accountReference,
                'amount' => $data->amount,
                'payer_name' => $data->payerName,
                'payer_phone' => $data->payerPhone,
                'paid_at' => $data->paidAt,
                'raw_payload' => $data->rawPayload,
                'status' => 'unmatched',
            ]);

            $this->attemptAutoMatch($txn);

            return $txn;
        });
    }

    protected function attemptAutoMatch(BankTransaction $txn): void
    {
        if (!$txn->account_reference) {
            return;
        }

        // Assumes you instruct parents to use the student's admission_number
        // as the account reference — same convention as your M-Pesa paybill.
        $invoice = Invoice::whereHas('student', function ($q) use ($txn) {
            $q->where('userID', trim($txn->account_reference));
        })
            ->where('balance', '>', 0)
            ->orderBy('created_at')
            ->first();

        if (!$invoice) {
            return; // stays unmatched, goes to review queue
        }

        $payment = Payment::create([
            'payment_number' => $this->generatePaymentNumber(),
            'invoice_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'method' => 'bank',
            'gateway' => $txn->bank,
            'gateway_transaction_id' => $txn->transaction_ref,
            'amount' => $txn->amount,
            'reference_number' => $txn->transaction_ref,
            'paid_on' => $txn->paid_at->toDateString(),
            'notes' => "Auto-matched via {$txn->bank} IPN",
        ]);

        $txn->update([
            'status' => 'matched',
            'matched_payment_id' => $payment->id,
            'matched_at' => now(),
        ]);
    }

    protected function generatePaymentNumber(): string
    {
        return 'RCT-' . now()->year . '-' . str_pad(
                (Payment::whereYear('created_at', now()->year)->count() + 1),
                6, '0', STR_PAD_LEFT
            );
    }
}
