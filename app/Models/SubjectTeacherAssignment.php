<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubjectTeacherAssignment extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = ['id', 'user_id', 'learning_area_id', 'stream_id', 'academic_year', 'status'];

    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function learningArea(): BelongsTo { return $this->belongsTo(LearningArea::class); }
    public function stream(): BelongsTo { return $this->belongsTo(Stream::class); }
}
