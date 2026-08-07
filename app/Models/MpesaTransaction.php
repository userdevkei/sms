<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks every STK push attempt — including ones that time out, get
 * cancelled by the user, or fail — so the admin Payments page can show
 * unsuccessful attempts, not just completed Payment rows. A successful
 * callback creates a real Payment and links it here via payment_id.
 */
class MpesaTransaction extends Model
{
    use HasStringId;

    protected $fillable = [
        'id', 'user_id', 'invoice_id', 'checkout_request_id', 'merchant_request_id',
        'phone_number', 'amount', 'status', 'result_code', 'result_description', 'payment_id',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
}
