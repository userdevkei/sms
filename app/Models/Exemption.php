<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exemption extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = [
        'id', 'user_id', 'votehead_id', 'type', 'value', 'academic_year', 'term',
        'reason', 'status', 'requested_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'value' => 'decimal:2'];
    }

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function votehead(): BelongsTo { return $this->belongsTo(Votehead::class); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeLabel(): string
    {
        return $this->votehead ? "On: {$this->votehead->name}" : 'On: Whole invoice';
    }
}
