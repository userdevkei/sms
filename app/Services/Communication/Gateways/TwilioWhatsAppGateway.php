<?php

// app/Services/Communication/Gateways/TwilioWhatsAppGateway.php
namespace App\Services\Communication\Gateways;

use Illuminate\Support\Facades\Http;

class TwilioWhatsAppGateway implements GatewayInterface
{
    protected array $config = [];
    public function configure(array $config): void { $this->config = $config; }

    public function send(string $destination, string $body, ?string $subject = null): array
    {
        $response = Http::asForm()
            ->withBasicAuth($this->config['account_sid'], $this->config['auth_token'])
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->config['account_sid']}/Messages.json", [
                'From' => 'whatsapp:' . $this->config['from'],
                'To'   => 'whatsapp:' . $destination,
                'Body' => $body,
            ]);

        return [
            'success'    => $response->successful(),
            'message_id' => $response->json('sid'),
            'error'      => $response->successful() ? null : $response->json('message', $response->body()),
        ];
    }
}
