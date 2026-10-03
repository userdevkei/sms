<?php
// app/Services/Communication/Gateways/AfricasTalkingSmsGateway.php
namespace App\Services\Communication\Gateways;

use Illuminate\Support\Facades\Http;

class AfricasTalkingSmsGateway implements GatewayInterface
{
    protected array $config = [];
    public function configure(array $config): void { $this->config = $config; }

    public function send(string $destination, string $body, ?string $subject = null): array
    {
        $response = Http::asForm()
            ->withHeaders(['apiKey' => $this->config['api_key'], 'Accept' => 'application/json'])
            ->post('https://api.africastalking.com/version1/messaging', [
                'username' => $this->config['username'],
                'to'       => $destination,
                'message'  => $body,
                'from'     => $this->config['sender_id'] ?? null,
            ]);

        $recipient = $response->json('SMSMessageData.Recipients.0');

        return [
            'success'    => $response->successful() && ($recipient['status'] ?? null) === 'Success',
            'message_id' => $recipient['messageId'] ?? null,
            'error'      => $response->successful() ? ($recipient['status'] ?? null) : $response->body(),
        ];
    }
}
