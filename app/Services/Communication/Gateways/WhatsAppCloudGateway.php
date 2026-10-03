<?php

// app/Services/Communication/Gateways/WhatsAppCloudGateway.php
namespace App\Services\Communication\Gateways;

use Illuminate\Support\Facades\Http;

class WhatsAppCloudGateway implements GatewayInterface
{
    protected array $config = [];
    public function configure(array $config): void { $this->config = $config; }

    public function send(string $destination, string $body, ?string $subject = null): array
    {
        // Free-form text only works inside Meta's 24h session window. For anything
        // outside that window (e.g. a first-touch notification), this call will
        // fail with error 131047 — swap to a template payload for those cases.
        $response = Http::withToken($this->config['access_token'])
            ->post("https://graph.facebook.com/v20.0/{$this->config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to'   => ltrim($destination, '+'),
                'type' => 'text',
                'text' => ['body' => $body],
            ]);

        return [
            'success'    => $response->successful(),
            'message_id' => $response->json('messages.0.id'),
            'error'      => $response->successful() ? null : $response->json('error.message', $response->body()),
        ];
    }
}
