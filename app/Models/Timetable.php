<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timetable extends Model
{
    use HasStringId, SoftDeletes, LogsActivity;

    protected $fillable = ['id', 'name', 'academic_year', 'term', 'status', 'generated_at', 'generated_by', 'approved_at', 'approved_by', 'published_at', 'published_by', 'generation_notes'];

    protected $casts = [
        'generation_notes' => 'array',
        'generated_at' => 'datetime', 'approved_at' => 'datetime', 'published_at' => 'datetime',
    ];

    public function gradeLevels(): BelongsToMany
    {
        return $this->belongsToMany(GradeLevel::class, 'timetable_grade_levels');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }

    public function generatedBy() { return $this->belongsTo(User::class, 'generated_by'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
    public function publishedBy() { return $this->belongsTo(User::class, 'published_by'); }
    public function slotGroupSelections(): HasMany { return $this->hasMany(TimetableSlotGroupSelection::class); }
}
