<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgressionException extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = [
        'id', 'user_id', 'enrollment_id', 'type', 'reason', 'new_academic_year',
        'status', 'requested_by', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function enrollment(): BelongsTo { return $this->belongsTo(StudentEnrollment::class, 'enrollment_id'); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewedBy(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'repeat'          => 'Repeat Grade',
            'transferred_out' => 'Transferred Out',
            'withdrawn'       => 'Withdrawn',
            'deceased'        => 'Deceased',
        };
    }
}
