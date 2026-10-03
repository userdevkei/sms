<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationRecipient extends Model
{
    use HasStringId, LogsActivity;

    protected $fillable = ['id', 'communication_id', 'recipient_type', 'recipient_id', 'destination', 'status', 'gateway_message_id', 'error', 'sent_at'];

    protected function casts(): array { return ['sent_at' => 'datetime']; }

    public function communication(): BelongsTo { return $this->belongsTo(Communication::class); }
}
