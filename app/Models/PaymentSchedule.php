<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentSchedule extends Model
{
    use HasStringId, SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'period'        => 'date',
        'approved_at'   => 'datetime',
        'paid_on'       => 'date',
        'rate_snapshot' => 'array',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    public function lines()
    {
        return $this->hasMany(PaymentScheduleLine::class)->orderBy('full_name');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /** Statutory remittance due date (9th of following month by default). */
    public function remittanceDue(): \Carbon\Carbon
    {
        return $this->period->copy()->addMonthNoOverflow()->day(config('kenya_payroll.remittance_day', 9));
    }
}
