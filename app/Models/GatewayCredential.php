<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayCredential extends Model
{
    use HasStringId, LogsActivity;

    protected $fillable = ['id', 'gateway_id', 'key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'encrypted'];
    }

    public function gateway(): BelongsTo { return $this->belongsTo(Gateway::class); }
}
