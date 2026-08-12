<?php
namespace App\Services\Banking\Handlers;

use App\DataTransferObjects\BankTransactionData;
use App\Models\Gateway;
use App\Services\Banking\Contracts\BankWebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KcbWebhookHandler implements BankWebhookHandler
{
    public function verify(Request $request): bool
    {
        $signature = $request->header('Signature');
        if (!$signature) {
            return false;
        }

        if (app()->environment('local', 'staging') && $signature === 'test-bypass') {
            Log::warning('KCB IPN: signature check bypassed — dev/staging only, not valid for real payloads');
            return true;
        }

        $publicKey = Gateway::where('provider', 'kcb')
            ->where('is_active', true)
            ->value('config->kcb_public_key');

        if (!$publicKey) {
            Log::error('KCB IPN: no public key configured for active gateway');
            return false;
        }

        $publicKeyResource = openssl_pkey_get_public($publicKey);
        if (!$publicKeyResource) {
            Log::error('KCB IPN: configured public key failed to parse');
            return false;
        }

        $result = openssl_verify(
            $request->getContent(),
            base64_decode($signature),
            $publicKeyResource,
            OPENSSL_ALGO_SHA256
        );

        return $result === 1;
    }

    public function shouldProcess(Request $request): bool
    {
        return true; // no status/result field in the payload itself — confirm with KCB whether failures also trigger IPN
    }

    public function parse(Request $request): BankTransactionData
    {
        $data = $request->json()->all();

        return new BankTransactionData(
            bank: 'kcb',
            transactionRef: $data['transactionReference'] ?? $data['transactionId'],
            accountReference: $data['customerReference'] ?? null,
            amount: (float) ($data['transactionAmount'] ?? $data['amount']),
            payerName: $data['customerName'] ?? null,
            payerPhone: $data['customerMobileNumber'] ?? null,
            paidAt: \DateTimeImmutable::createFromFormat('YmdHi', $data['timestamp'] ?? '')
                ?: new \DateTimeImmutable(),
            rawPayload: $data,
        );
    }

    public function acknowledgement(Request $request): array
    {
        // KCB's documented sample response echoes an id back and returns
        // statusCode "0" with statusMessage on success — confirm the exact
        // field this ack should echo (requestId vs transactionId) once
        // you've run a real test, the two field names showed up inconsistently
        // between the request sample and response sample.
        return [
            'transactionId' => $request->json('requestId') ?? $request->json('transactionReference'),
            'statusCode' => '0',
            'statusMessage' => 'Notification received successfully',
        ];
    }
}
