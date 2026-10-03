<?php

// app/Services/Communication/Gateways/SmtpEmailGateway.php
namespace App\Services\Communication\Gateways;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

class SmtpEmailGateway implements GatewayInterface
{
    protected array $config = [];
    public function configure(array $config): void { $this->config = $config; }

    public function send(string $destination, string $body, ?string $subject = null): array
    {
        try {
            $transport = new EsmtpTransport($this->config['host'], (int) $this->config['port'], $this->config['encryption'] === 'ssl');
            $transport->setUsername($this->config['username']);
            $transport->setPassword($this->config['password']);

            $email = (new Email())
                ->from($this->config['from_address'])
                ->to($destination)
                ->subject($subject ?? config('app.name'))
                ->html($body);

            (new Mailer($transport))->send($email);

            return ['success' => true, 'message_id' => null, 'error' => null];
        } catch (\Throwable $e) {
            return ['success' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }
    }
}
