<?php
namespace App\Services\Banking\Contracts;

use App\DataTransferObjects\BankTransactionData;
use Illuminate\Http\Request;

interface BankWebhookHandler
{
    public function verify(Request $request): bool;
    public function shouldProcess(Request $request): bool;
    public function parse(Request $request): BankTransactionData;
    public function acknowledgement(Request $request): array; // now takes $request
}
