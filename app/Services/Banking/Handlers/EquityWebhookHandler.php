<?php

namespace App\Services\Banking\Handlers;

use App\DataTransferObjects\BankTransactionData;
use App\Services\Banking\Contracts\BankWebhookHandler;
use Illuminate\Http\Request;

class EquityWebhookHandler implements BankWebhookHandler
{
    public function verify(Request $request): bool
    {
        return $request->getUser() === config('services.equity.ipn_user')
            && $request->getPassword() === config('services.equity.ipn_password');
    }

    public function shouldProcess(Request $request): bool
    {
        // Equity sends IPN for both SUCCESS and FAILED — only FAILED ones
        // still hit your endpoint for visibility, don't create a payment for them.
        return $request->json('transaction.status') === 'SUCCESS';
    }

    public function parse(Request $request): BankTransactionData
    {
        $data = $request->json()->all();
        $transaction = $data['transaction'];

        return new BankTransactionData(
            bank: 'equity',
            transactionRef: $transaction['reference'],
            accountReference: $transaction['billNumber'] ?? null,
            amount: (float) $transaction['amount'],
            payerName: $data['customer']['name'] ?? null,
            payerPhone: $data['customer']['mobileNumber'] ?? null,
            paidAt: new \DateTimeImmutable($transaction['date']),
            rawPayload: $data,
        );
    }

    public function acknowledgement(): array
    {
        return ['status' => 'SUCCESS'];
    }
}
