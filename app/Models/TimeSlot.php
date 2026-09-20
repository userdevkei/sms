<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeSlot extends Model
{
    use HasStringId, SoftDeletes, LogsActivity;

    protected $fillable = ['id', 'time_slot_group_id', 'day_of_week', 'start_time', 'end_time', 'type', 'label', 'sequence', 'is_break', 'status'];
    protected $casts = ['is_break' => 'boolean'];

    public const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? '—';
    }
    public function group(): BelongsTo { return $this->belongsTo(TimeSlotGroup::class, 'time_slot_group_id'); }
}
