<?php

namespace App\Services\Payments;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wraps the M-Pesa Daraja "Lipa na M-Pesa Online" (STK Push) API using
 * whichever PaymentGateway row is currently active with provider='mpesa'.
 * Reads credentials via $gateway->config() (decrypted transparently by the
 * GatewayCredential model's 'encrypted' cast).
 */
class MpesaStkPushService
{
    private function activeGateway(): PaymentGateway
    {
        $gateway = PaymentGateway::active();

        abort_unless($gateway && $gateway->provider === 'mpesa', 422, 'No active M-Pesa payment gateway is configured.');

        return $gateway;
    }

    private function baseUrl(string $environment): string
    {
        return $environment === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    private function accessToken(array $config): string
    {
        $response = Http::withBasicAuth($config['consumer_key'], $config['consumer_secret'])
            ->get($this->baseUrl($config['environment']) . '/oauth/v1/generate?grant_type=client_credentials');

        abort_unless($response->successful(), 502, 'Could not authenticate with M-Pesa.');

        return $response->json('access_token');
    }

    /**
     * Initiates an STK push to the given phone number. Returns Safaricom's
     * response array (contains CheckoutRequestID, used to match the async
     * callback to this attempt).
     */
    public function initiate(string $phone, float $amount, string $accountReference, string $description): array
    {
        $gateway = $this->activeGateway();
        $config = $gateway->config();

        $token = $this->accessToken($config);

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($config['shortcode'] . $config['passkey'] . $timestamp);

        $response = Http::withToken($token)->post($this->baseUrl($config['environment']) . '/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $config['shortcode'],
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int) round($amount),
            'PartyA'            => $this->normalizePhone($phone),
            'PartyB'            => $config['shortcode'],
            'PhoneNumber'       => $this->normalizePhone($phone),
            'CallBackURL'       => $config['callback_url'],
            'AccountReference'  => $accountReference,
            'TransactionDesc'   => $description,
        ]);

        if (! $response->successful()) {
            Log::warning('M-Pesa STK push failed', ['response' => $response->body()]);
            abort(502, 'M-Pesa did not accept the payment request.');
        }

        return $response->json();
    }

    /** Converts 07XXXXXXXX / +2547XXXXXXXX / 2547XXXXXXXX to Safaricom's required 2547XXXXXXXX format. */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '254' . substr($phone, 1);
        }

        if (str_starts_with($phone, '254')) {
            return $phone;
        }

        return '254' . $phone;
    }
}
