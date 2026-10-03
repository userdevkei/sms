<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentScheduleLine extends Model
{
    use HasStringId, SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = ['breaches_one_third' => 'boolean'];

    public function schedule()
    {
        return $this->belongsTo(PaymentSchedule::class, 'payment_schedule_id');
    }

    public function items()
    {
        return $this->hasMany(PaymentScheduleLineItem::class, 'payment_schedule_line_id');
    }
}
