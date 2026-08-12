<?php
namespace App\DataTransferObjects;

readonly class BankTransactionData
{
    public function __construct(
        public string $bank,               // equity | coop | kcb
        public string $transactionRef,
        public ?string $accountReference,
        public float $amount,
        public ?string $payerName,
        public ?string $payerPhone,
        public \DateTimeImmutable $paidAt,
        public array $rawPayload,
    ) {}
}
