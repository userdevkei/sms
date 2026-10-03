<?php

// app/Services/Communication/GatewayResolver.php
namespace App\Services\Communication;

use App\Models\Gateway;
use App\Services\Communication\Gateways\GatewayInterface;
use RuntimeException;

class GatewayResolver
{
    public function resolve(string $channel): GatewayInterface
    {
        $gateway = Gateway::activeForType($channel);

        if (! $gateway) {
            throw new RuntimeException("No active {$channel} gateway is configured.");
        }

        // config groups: email lives under 'smtp', the rest match the channel name
        $group = $channel === 'email' ? 'smtp' : $channel;

        $driverClass = config("communication.drivers.{$group}.{$gateway->provider}");

        if (! $driverClass) {
            throw new RuntimeException("Unknown provider '{$gateway->provider}' for {$channel}.");
        }

        $driver = app($driverClass);
        $driver->configure($gateway->config());

        return $driver;
    }
}
