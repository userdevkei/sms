<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TermResultCompletion extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = ['id', 'user_id', 'academic_year', 'term_number', 'completed_at', 'recorded_by'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
