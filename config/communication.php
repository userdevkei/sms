<?php

// config/communication.php
return [
    'drivers' => [
        'sms' => [
            'africas_talking' => \App\Services\Communication\Gateways\AfricasTalkingSmsGateway::class,
            'twilio'          => \App\Services\Communication\Gateways\TwilioSmsGateway::class,
            'custom'          => \App\Services\Communication\Gateways\CustomSmsGateway::class,
        ],
        'whatsapp' => [
            'whatsapp_cloud' => \App\Services\Communication\Gateways\WhatsAppCloudGateway::class,
            'twilio'         => \App\Services\Communication\Gateways\TwilioWhatsAppGateway::class,
            'custom'         => \App\Services\Communication\Gateways\CustomWhatsAppGateway::class,
        ],
        'smtp' => [
            'smtp' => \App\Services\Communication\Gateways\SmtpEmailGateway::class,
        ],
    ],
    'batch_size' => 100,
];
