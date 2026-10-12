<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\MpesaTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\MpesaStkPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuardianPaymentController extends Controller
{
    private const PHONE_RULE = ['required', 'string', 'regex:/^(\+?254|0)[17]\d{8}$/'];

    public function index(Request $request, User $child)
    {
        return view('guardian.payments', [
            'child'         => $child,
            'user'          => $request->user(), // the guardian: STK phone prefill
            'transactions'  => MpesaTransaction::where('user_id', $child->id)->latest()->get(),
            'canPay'        => $this->canPay($request),
            'initiateUrl'   => route('guardian.students.payments.initiate', $child->id),
            'statusUrlBase' => url("guardian/students/{$child->id}/payments/status"),
            'retryUrlBase'  => url("guardian/students/{$child->id}/payments/retry"),
        ]);
    }

    /** Sidebar "Pay Fees" and the dashboard Pay button land here, with the modal auto-opened. */
    public function pay(User $child)
    {
        return redirect()->route('guardian.students.payments', ['child' => $child->id, 'pay' => 1]);
    }

    public function initiate(Request $request, User $child, MpesaStkPushService $mpesa): JsonResponse
    {
//        $this->authorizePayer($request);

        $data = $request->validate([
            'phone'  => self::PHONE_RULE,
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        return $this->sendStk($request, $child, $mpesa, $data, 'STK push sent. Check your phone.');
    }

    public function retry(Request $request, User $child, MpesaTransaction $txn, MpesaStkPushService $mpesa): JsonResponse
    {
        $this->authorizePayer($request);
        abort_unless($txn->user_id === $child->id, 404);
        abort_unless(in_array($txn->status, ['pending', 'failed', 'cancelled']), 422, 'This transaction cannot be retried.');

        $data = $request->validate([
            'phone'  => self::PHONE_RULE,
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        return $this->sendStk($request, $child, $mpesa, $data, 'Retry initiated. Check your phone.');
    }

    public function status(Request $request, User $child, MpesaTransaction $txn, MpesaStkPushService $mpesa): JsonResponse
    {
        abort_unless($txn->user_id === $child->id, 404);

        // Only ask Safaricom while we're still pending locally.
        if ($txn->status === 'pending' && $txn->checkout_request_id) {
            try {
                $result = $mpesa->query($txn->checkout_request_id);
                $code   = $result['ResultCode'] ?? null;

                Log::info('Guardian M-Pesa status query', [
                    'transaction_id' => $txn->id,
                    'result_code'    => $code,
                    'result_desc'    => $result['ResultDesc'] ?? null,
                ]);

                if ($code !== null) {
                    if ((int) $code === 0) {
                        $items = collect(data_get($result, 'CallbackMetadata.Item', []))
                            ->mapWithKeys(fn ($i) => [$i['Name'] => $i['Value'] ?? null]);

                        $this->recordSuccess(
                            $txn,
                            $items->get('MpesaReceiptNumber'),
                            $result['ResultDesc'] ?? 'Confirmed via query',
                            'Auto-recorded via M-Pesa STK query fallback (guardian portal)'
                        );
                    } elseif (in_array((int) $code, [1032, 1037])) {
                        $txn->update([
                            'status'             => (int) $code === 1032 ? 'cancelled' : 'failed',
                            'result_description' => $result['ResultDesc'] ?? null,
                        ]);
                    }
                    // Other codes = still processing: leave as pending.
                }
            } catch (\Throwable $e) {
                Log::warning('Guardian M-Pesa status query failed', ['transaction_id' => $txn->id, 'error' => $e->getMessage()]);
            }

            $txn->refresh();
        }

        return response()->json([
            'status'             => $txn->status,
            'payment_id'         => $txn->payment_id, // the M-Pesa receipt
            'result_description' => $txn->result_description,
        ]);
    }

    /* ───────────── helpers ───────────── */

    private function sendStk(Request $request, User $child, MpesaStkPushService $mpesa, array $data, string $message): JsonResponse
    {
        try {
            // The child's admission number is the account reference, so it reconciles like any student payment.
            $response = $mpesa->initiate($data['phone'], (float) $data['amount'], $child->userID, 'School fees payment');
        } catch (\Throwable $e) {
            Log::error('Guardian STK initiate failed', ['child' => $child->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Could not reach M-Pesa. Please try again.'], 502);
        }

        if (empty($response['CheckoutRequestID'])) {
            Log::warning('Guardian STK initiate rejected', ['child' => $child->id, 'response' => $response]);

            return response()->json([
                'message' => $response['errorMessage'] ?? $response['ResponseDescription'] ?? 'M-Pesa did not accept the request. Please try again.',
            ], 422);
        }

        $txn = MpesaTransaction::create([
            'user_id'             => $child->id,                // posts to the STUDENT's ledger
            'paid_by_guardian_id' => $request->user()->id,      // traceability
            'checkout_request_id' => $response['CheckoutRequestID'],
            'merchant_request_id' => $response['MerchantRequestID'] ?? null,
            'phone_number'        => $data['phone'],
            'amount'              => $data['amount'],
            'status'              => 'pending',
        ]);

        return response()->json([
            'message'        => $message,
            'transaction_id' => $txn->id,
            'status_url'     => route('guardian.students.payments.status', ['child' => $child->id, 'txn' => $txn->id]),
        ]);
    }

    /** Row-locked so the callback and a status poll can't both create a Payment. */
    private function recordSuccess(MpesaTransaction $txn, ?string $receipt, string $desc, string $note): void
    {
        DB::transaction(function () use ($txn, $receipt, $desc, $note) {
            $fresh = MpesaTransaction::whereKey($txn->getKey())->lockForUpdate()->first();

            if ($fresh->status === 'success') {
                return; // callback (or another poll) already settled it
            }

            $fresh->update([
                'status'             => 'success',
                'payment_id'         => $receipt,
                'result_description' => $desc,
                'paid_at'            => now(),
            ]);

            if ($receipt && Payment::where('reference_number', $receipt)->exists()) {
                return;
            }

            $count = Payment::query()->count() + 1;

            Payment::create([
                'payment_number'      => 'RCT-' . date('Y') . '-' . str_pad($count, 6, '0', STR_PAD_LEFT),
                'invoice_id'          => null,
                'user_id'             => $fresh->user_id,
                'paid_by_guardian_id' => $fresh->paid_by_guardian_id,
                'method'              => 'mpesa',
                'amount'              => $fresh->amount,
                'reference_number'    => $receipt,
                'paid_on'             => now(),
                'received_by'         => null,
                'notes'               => $note,
            ]);
        });
    }

    private function canPay(Request $request): bool
    {
        $link = $request->attributes->get('guardian_link'); // set by guardian.student middleware

        return $request->user()->hasPermission('guardian-portal.pay') && (bool) ($link?->can_pay);
    }

    private function authorizePayer(Request $request): void
    {
        abort_unless($this->canPay($request), 403, 'You are not allowed to make payments for this student.');
    }
}
