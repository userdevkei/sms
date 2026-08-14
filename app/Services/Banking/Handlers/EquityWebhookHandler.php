<?php
namespace App\Services\Banking\Handlers;

use App\DataTransferObjects\BankTransactionData;
use App\Models\Gateway;
use App\Services\Banking\Contracts\BankWebhookHandler;
use Illuminate\Http\Request;

class EquityWebhookHandler implements BankWebhookHandler
{
    public function verify(Request $request): bool
    {
        $gateway = Gateway::where('provider', 'equity')
            ->where('is_active', true)
            ->first();

        if (!$gateway) {
            return false;
        }

        $config = $gateway->config();

        return hash_equals((string) ($config['ipn_username'] ?? ''), (string) $request->getUser())
            && hash_equals((string) ($config['ipn_password'] ?? ''), (string) $request->getPassword());
    }

    public function shouldProcess(Request $request): bool
    {
        // Equity IPN fires for both SUCCESS and FAILED — only reconcile SUCCESS.
        return $request->json('transaction.status') === 'SUCCESS';
    }

    public function parse(Request $request): BankTransactionData
    {
        $data = $request->json()->all();
        $transaction = $data['transaction'];

        return new BankTransactionData(
            bank: 'equity',
            transactionRef: $transaction['reference'],
            accountReference: trim((string) ($transaction['billNumber'] ?? '')) ?: null,
            amount: (float) $transaction['amount'],
            payerName: $data['customer']['name'] ?? null,
            payerPhone: $data['customer']['mobileNumber'] ?? null,
            paidAt: new \DateTimeImmutable($transaction['date']),
            rawPayload: $data,
        );
    }

    public function acknowledgement(Request $request): array
    {
        return ['status' => 'SUCCESS'];
    }
}
