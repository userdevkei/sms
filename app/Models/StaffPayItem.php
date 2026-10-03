<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffPayItem extends Model
{
    use HasStringId, SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = ['amount' => 'decimal:2', 'is_active' => 'boolean'];

    public const KINDS = [
        'allowance'         => 'Allowance (taxable)',
        'reimbursement'     => 'Reimbursement (non-taxable)',
        'pension'           => 'Pension – own contribution',
        'insurance_premium' => 'Insurance premium (relief)',
        'mortgage_interest' => 'Mortgage interest',
        'deduction'         => 'Deduction (sacco/loan/advance)',
    ];

    public function payee()
    {
        return $this->belongsTo(StaffPayee::class, 'staff_payee_id');
    }
}
