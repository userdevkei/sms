<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = [
        'id', 'payment_number', 'invoice_id', 'user_id', 'method', 'amount',
        'reference_number', 'paid_on', 'received_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_on' => 'date'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function receivedBy(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
}
