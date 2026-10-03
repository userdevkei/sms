<?php

namespace App\Services\Communication\Gateways;

interface GatewayInterface
{
    /** @return array{success: bool, message_id: ?string, error: ?string} */
    public function send(string $destination, string $body, ?string $subject = null): array;
    public function configure(array $config): void;
}
