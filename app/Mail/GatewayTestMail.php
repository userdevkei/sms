<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GatewayTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(protected string $gatewayName, protected ?string $customMessage = null) {}

    public function build(): self
    {
        return $this->subject('Test email — ' . $this->gatewayName)
            ->view('emails.gateway-test')
            ->with([
                'gatewayName' => $this->gatewayName,
                'customMessage' => $this->customMessage,
            ]);
    }
}
