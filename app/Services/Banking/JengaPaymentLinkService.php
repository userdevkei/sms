<?php

namespace App\Services\Banking;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class JengaPaymentLinkService
{
    protected string $baseUrl;

    public function __construct(protected array $config)
    {
        $this->baseUrl = $config['environment'] === 'live'
            ? 'https://api.finserve.africa'
            : 'https://uat.finserve.africa';
    }

    public function getAccessToken(): string
    {
        return Cache::remember('jenga_access_token', now()->addMinutes(50), function () {
            $response = Http::withHeaders([
                'Api-Key' => $this->config['api_key'],
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/authentication/api/v3/authenticate/merchant", [
                'merchantCode' => $this->config['merchant_code'],
                'consumerSecret' => $this->config['consumer_secret'],
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('Jenga auth failed: ' . $response->body());
            }

            return $response->json('accessToken');
        });
    }

    protected function sign(string $data): string
    {
        $privateKey = openssl_pkey_get_private(
            file_get_contents(storage_path('app/jenga_private.pem'))
        );

        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    public function createPaymentLink(array $customer, array $paymentLink): array
    {
        $signatureString = $paymentLink['expiryDate']
            . $paymentLink['amount']
            . $paymentLink['currency']
            . $paymentLink['amountOption']
            . $paymentLink['externalRef'];

        $response = Http::withToken($this->getAccessToken())
            ->withHeaders(['Signature' => $this->sign($signatureString)])
            ->post("{$this->baseUrl}/api-checkout/api/v1/create/payment-link", [
                'customers' => [$customer],
                'paymentLink' => $paymentLink,
                'notifications' => ['EMAIL'],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Payment link creation failed: ' . $response->body());
        }

        return $response->json();
    }
}
