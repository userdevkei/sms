<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\MpesaTransaction;
use App\Models\Payment;
use App\Services\Payments\MpesaStkPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StudentPaymentController extends Controller
{
    public function __construct(private MpesaStkPushService $mpesa) {}

    public function pay(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('student'), 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'phone'  => ['required', 'string', 'max:15'],
        ]);

        try {
            $result = $this->mpesa->initiate(
                $validated['phone'],
                $validated['amount'],
                $user->userID ?: $user->id,
                'School fees payment'
            );
        } catch (\Throwable $e) {
            Log::error('STK push initiation failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Could not initiate payment. Please try again.'], 502);
        }

        MpesaTransaction::query()->create([
            'user_id'              => $user->id,
            'checkout_request_id'  => $result['CheckoutRequestID'] ?? null,
            'merchant_request_id'  => $result['MerchantRequestID'] ?? null,
            'phone_number'         => $validated['phone'],
            'amount'               => $validated['amount'],
            'status'                => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Check your phone and enter your M-Pesa PIN to complete the payment.']);
    }

    /**
     * Safaricom's async callback. No auth — this endpoint must be public,
     * matching whatever callback_url was configured on the active gateway.
     * Idempotent: a duplicate callback for an already-resolved transaction
     * is a no-op rather than double-crediting the student.
     */
    public function callback(Request $request): JsonResponse
    {
        $body = $request->input('Body.stkCallback', []);
        $checkoutRequestId = $body['CheckoutRequestID'] ?? null;
        $resultCode = $body['ResultCode'] ?? null;

        $transaction = MpesaTransaction::query()->where('checkout_request_id', $checkoutRequestId)->first();

        if (! $transaction || $transaction->status !== 'pending') {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']); // idempotent no-op
        }

        if ((int) $resultCode !== 0) {
            $transaction->update([
                'status'             => (int) $resultCode === 1032 ? 'cancelled' : 'failed',
                'result_code'        => $resultCode,
                'result_description' => $body['ResultDesc'] ?? null,
            ]);

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $items = collect($body['CallbackMetadata']['Item'] ?? [])->keyBy('Name');
        $mpesaReceipt = $items->get('MpesaReceiptNumber')['Value'] ?? null;
        $amountPaid = $items->get('Amount')['Value'] ?? $transaction->amount;

        $payment = Payment::query()->create([
            'payment_number'    => $mpesaReceipt ?? ('MPESA-' . now()->timestamp),
            'user_id'           => $transaction->user_id,
            'method'            => 'mpesa',
            'amount'            => $amountPaid,
            'reference_number'  => $mpesaReceipt,
            'paid_on'           => now(),
        ]);

        // Apply this payment against the student's oldest unpaid invoice
        // first — a reasonable default since the statement is a running
        // account, not a per-invoice checkout. Flag if you want the student
        // to choose which invoice a payment applies to instead.
        $invoice = Invoice::where('user_id', $transaction->user_id)
            ->where('status', '!=', 'paid')
            ->oldest('created_at')
            ->first();

        if ($invoice) {
            $payment->update(['invoice_id' => $invoice->id]);
            $invoice->recalculate();
        }

        $transaction->update([
            'status'      => 'success',
            'result_code' => $resultCode,
            'payment_id'  => $payment->id,
        ]);

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
