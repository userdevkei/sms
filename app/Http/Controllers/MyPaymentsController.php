<?php

namespace App\Http\Controllers;

use App\Models\MpesaTransaction;
use App\Models\Payment;
use App\Services\Payments\MpesaStkPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MyPaymentsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('student'), 403);

        $transactions = MpesaTransaction::where('user_id', $user->id)
            ->latest()
            ->get();

        return view('finance.my-payments.index', compact('transactions', 'user'));
    }

    public function initiate(Request $request, MpesaStkPushService $mpesa)
    {
        $user = $request->user();
        abort_unless($user->hasRole('student'), 403);

        $data = $request->validate([
            'phone'  => ['required', 'string', 'regex:/^(\+?254|0)[17]\d{8}$/'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $response = $mpesa->initiate(
            $data['phone'],
            (float) $data['amount'],
            $user->userID,
            'School fees payment'
        );

        $transaction = MpesaTransaction::create([
            'user_id'              => $user->id,
            'checkout_request_id'  => $response['CheckoutRequestID'] ?? null,
            'merchant_request_id'  => $response['MerchantRequestID'] ?? null,
            'phone_number'         => $data['phone'],
            'amount'               => $data['amount'],
            'status'               => 'pending',
        ]);

        return response()->json([
            'message'        => 'STK push sent. Check your phone.',
            'transaction_id' => $transaction->id,
            'status_url'     => route('finance.my-payments.status', $transaction),
        ]);
    }

    public function retry(Request $request, MpesaTransaction $transaction, MpesaStkPushService $mpesa)
    {
        $user = $request->user();
        abort_unless($user->hasRole('student') && $transaction->user_id === $user->id, 403);
        abort_unless(in_array($transaction->status, ['pending', 'failed', 'cancelled']), 422, 'This transaction cannot be retried.');

        $data = $request->validate([
            'phone'  => ['required', 'string', 'regex:/^(\+?254|0)[17]\d{8}$/'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $response = $mpesa->initiate(
            $data['phone'],
            (float) $data['amount'],
            $user->userID,
            'School fees payment'
        );

        $newTransaction = MpesaTransaction::create([
            'user_id'              => $user->id,
            'checkout_request_id'  => $response['CheckoutRequestID'] ?? null,
            'merchant_request_id'  => $response['MerchantRequestID'] ?? null,
            'phone_number'         => $data['phone'],
            'amount'               => $data['amount'],
            'status'               => 'pending',
        ]);

        return response()->json([
            'message'        => 'Retry initiated. Check your phone.',
            'transaction_id' => $newTransaction->id,
            'status_url'     => route('finance.my-payments.status', $newTransaction),
        ]);
    }

public function status(Request $request, MpesaTransaction $transaction, MpesaStkPushService $mpesa)
{
    $user = $request->user();
    abort_unless($user->hasRole('student') && $transaction->user_id === $user->id, 403);

    // Only reach out to Safaricom if we're still pending locally —
    // no point querying once the callback (or a prior query) has resolved it.
    if ($transaction->status === 'pending' && $transaction->checkout_request_id) {
        try {
            $result = $mpesa->query($transaction->checkout_request_id);

            // ResultCode 0 = success, other numeric codes = failed/cancelled,
            // but Safaricom also returns 1037/1032-style "still processing"
            // errors distinguishable via ResultCode/ErrorCode — treat only
            // a definitive 0 or a definitive failure code as resolving.
            $resultCode = $result['ResultCode'] ?? null;

            if ($resultCode !== null) {
                if ((int) $resultCode === 0) {
                    $items = collect(data_get($result, 'CallbackMetadata.Item', []))
                        ->mapWithKeys(fn ($item) => [$item['Name'] => $item['Value'] ?? null]);

                    // Fall back to the query's own fields if CallbackMetadata
                    // isn't present (the query response shape differs slightly
                    // from the callback shape on some sandbox responses).
                    $mpesaReceipt = $items->get('MpesaReceiptNumber');

                    $transaction->update([
                        'status'             => 'success',
                        'payment_id'         => $mpesaReceipt,
                        'result_description' => $result['ResultDesc'] ?? 'Confirmed via query',
                        'paid_at'            => now(),
                    ]);

                    if (! \App\Models\Payment::where('reference_number', $mpesaReceipt)->exists()) {
                        $count = \App\Models\Payment::query()->count() + 1;
                        \App\Models\Payment::create([
                            'payment_number'   => 'RCT-' . date('Y') . '-' . str_pad($count, 6, '0', STR_PAD_LEFT),
                            'invoice_id'       => null,
                            'user_id'          => $transaction->user_id,
                            'method'           => 'mpesa',
                            'amount'           => $transaction->amount,
                            'reference_number' => $mpesaReceipt,
                            'paid_on'          => now(),
                            'received_by'      => null,
                            'notes'            => 'Auto-recorded via M-Pesa STK query fallback',
                        ]);
                    }
                } elseif (in_array((int) $resultCode, [1032, 1037])) {
                    // 1032: cancelled by user, 1037: timeout / no response
                    $transaction->update([
                        'status'             => (int) $resultCode === 1032 ? 'cancelled' : 'failed',
                        'result_description' => $result['ResultDesc'] ?? null,
                    ]);
                }
                // Other codes (e.g. still-processing errors) are left as pending —
                // we don't want a transient query error to wrongly mark it failed.
            }
        } catch (\Throwable $e) {
            // Query failures shouldn't break the polling UI — just log and
            // fall through to returning whatever's in the DB.
            Log::warning('M-Pesa status query fallback failed', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }

        $transaction->refresh();
    }

    return response()->json([
        'status'             => $transaction->status,
        'payment_id'         => $transaction->id,
        'result_description' => $transaction->result_description,
    ]);
}

    public function handle(Request $request)
{
    $payload = $request->all();
    Log::info('M-Pesa callback received', $payload);

    $stkCallback = data_get($payload, 'Body.stkCallback');
    if (! $stkCallback) {
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Ignored']);
    }

    $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
    $resultCode = $stkCallback['ResultCode'] ?? null;
    $resultDesc = $stkCallback['ResultDesc'] ?? null;

    $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

    if (! $transaction) {
        Log::warning('M-Pesa callback: no matching transaction', ['checkout_request_id' => $checkoutRequestId]);
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    // Guard against Safaricom retrying the same callback more than once.
    if ($transaction->status === 'success') {
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Already processed']);
    }

    if ($resultCode === 0) {
        $items = collect(data_get($stkCallback, 'CallbackMetadata.Item', []))
            ->mapWithKeys(fn ($item) => [$item['Name'] => $item['Value'] ?? null]);

        $mpesaReceipt = $items->get('MpesaReceiptNumber');
        $paidAt = $items->get('TransactionDate'); // format: YmdHis

        try {
            DB::transaction(function () use ($transaction, $resultDesc, $mpesaReceipt, $paidAt) {
                $transaction->update([
                    'status'              => 'success',
                    'payment_id'          => $mpesaReceipt,
                    'result_description'  => $resultDesc,
                    'paid_at'             => now(),
                ]);

                $count = Payment::query()->count() + 1;

                Payment::query()->create([
                    'payment_number'   => 'RCT-' . date('Y') . '-' . str_pad($count, 6, '0', STR_PAD_LEFT),
                    'invoice_id'       => null,
                    'user_id'          => $transaction->user_id,
                    'method'           => 'mpesa',
                    'amount'           => $transaction->amount,
                    'reference_number' => $mpesaReceipt,
                    'paid_on'          => $paidAt
                        ? \Carbon\Carbon::createFromFormat('YmdHis', $paidAt)
                        : now(),
                    'received_by'      => null,
                    'notes'            => 'Auto-recorded from M-Pesa STK push',
                ]);
            });
        } catch (\Throwable $e) {
            // Never let a Payment-creation failure block the MpesaTransaction
            // from being marked successful — log it and move on so the
            // student's status still updates correctly.
            Log::error('M-Pesa callback: failed to create Payment record', [
                'transaction_id' => $transaction->id,
                'error'           => $e->getMessage(),
            ]);
        }
    } else {
        $transaction->update([
            'status'              => in_array($resultCode, [1032]) ? 'cancelled' : 'failed',
            'result_description'  => $resultDesc,
        ]);
    }

    return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
}
}
