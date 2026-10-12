<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentChangeRequest extends Model
{
    protected $guarded = [];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function student(): BelongsTo   { return $this->belongsTo(User::class, 'student_id'); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo  { return $this->belongsTo(User::class, 'reviewed_by'); }
}
