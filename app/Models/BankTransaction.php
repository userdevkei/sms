<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasStringId;

    protected $fillable = [
        'bank', 'transaction_ref', 'account_reference', 'amount',
        'payer_name', 'payer_phone', 'paid_at', 'raw_payload',
        'status', 'matched_payment_id', 'matched_by', 'matched_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'paid_at' => 'datetime',
        'matched_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function matchedPayment()
    {
        return $this->belongsTo(Payment::class, 'matched_payment_id');
    }
}
