<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructureItem extends Model
{
    use HasStringId, LogsActivity;

    protected $fillable = ['id', 'fee_structure_id', 'votehead_id', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function votehead(): BelongsTo
    {
        return $this->belongsTo(Votehead::class);
    }
}
