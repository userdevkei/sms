<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PaymentScheduleLineItem extends Model
{
    use HasStringId, LogsActivity;
    protected $guarded = [];

    protected $casts = ['amount' => 'decimal:2'];

    public const KINDS = [
        'allowance'     => 'Taxable earning',
        'reimbursement' => 'Non-taxable reimbursement',
        'deduction'     => 'Deduction (post-tax)',
    ];

    public function line()
    {
        return $this->belongsTo(PaymentScheduleLine::class, 'payment_schedule_line_id');
    }
}
