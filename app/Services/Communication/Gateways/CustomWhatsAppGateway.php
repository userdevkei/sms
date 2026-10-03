<?php

namespace App\Services\Communication\Gateways;

use Illuminate\Support\Facades\Http;

class CustomWhatsAppGateway implements GatewayInterface
{
    protected array $config = [];
    public function configure(array $config): void { $this->config = $config; }

    public function send(string $destination, string $body, ?string $subject = null): array
    {
        $response = Http::withToken($this->config['api_key'] ?? '')
            ->post($this->config['endpoint_url'], ['to' => $destination, 'message' => $body]);

        return ['success' => $response->successful(), 'message_id' => null, 'error' => $response->successful() ? null : $response->body()];
    }
}
