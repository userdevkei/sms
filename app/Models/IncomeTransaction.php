<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomeTransaction extends Model
{
    use HasStringId, softDeletes, LogsActivity;

    protected $fillable = ['reference', 'transaction_date', 'academic_year', 'term', 'received_from', 'payment_method', 'description', 'total_amount', 'recorded_by'];
    protected $casts = ['transaction_date' => 'date', 'total_amount' => 'decimal:2'];

    public function items() { return $this->hasMany(IncomeTransactionItem::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }
}
