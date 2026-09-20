<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EducationLevel extends Model
{
    use HasStringId, softDeletes,LogsActivity;

    protected $fillable = ['id', 'name', 'code', 'sequence', 'description', 'status', 'default_time_slot_group_id'];

    public function gradeLevels(): HasMany
    {
        return $this->hasMany(GradeLevel::class)->orderBy('sequence');
    }

    public function defaultTimeSlotGroup(): BelongsTo { return $this->belongsTo(TimeSlotGroup::class, 'default_time_slot_group_id'); }
}
