<?php

namespace App\Http\Controllers;

use App\Models\MpesaTransaction;
use App\Services\Payments\MpesaStkPushService;
use Illuminate\Http\Request;
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

    public function status(Request $request, MpesaTransaction $transaction)
    {
        $user = $request->user();
        abort_unless($user->hasRole('student') && $transaction->user_id === $user->id, 403);

        \Log::info('data', $transaction->toArray());

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

        if ($resultCode === 0) {
            $items = collect(data_get($stkCallback, 'CallbackMetadata.Item', []))
                ->mapWithKeys(fn ($item) => [$item['Name'] => $item['Value'] ?? null]);

            $transaction->update([
                'status'              => 'success',
                'payment_id'          => $items->get('MpesaReceiptNumber'),
                'result_description'  => $resultDesc,
                'paid_at'             => now(),
            ]);
        } else {
            $transaction->update([
                'status'              => in_array($resultCode, [1032]) ? 'cancelled' : 'failed',
                'result_description'  => $resultDesc,
            ]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
