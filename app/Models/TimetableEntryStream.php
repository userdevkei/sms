<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class TimetableEntryStream extends Model
{
    use LogsActivity;
    protected $fillable = ['timetable_entry_id', 'stream_id'];
}
