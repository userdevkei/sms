<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeSlotGroup extends Model
{
    use HasStringId, SoftDeletes, LogsActivity;

    protected $fillable = ['id', 'name', 'code', 'description', 'status'];

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function educationLevels(): HasMany
    {
        return $this->hasMany(EducationLevel::class, 'default_time_slot_group_id');
    }
}
