<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseTransaction extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = ['id', 'reference', 'transaction_date', 'academic_year', 'term', 'vendor', 'payment_method', 'description', 'total_amount', 'recorded_by'];
    protected $casts = ['transaction_date' => 'date', 'total_amount' => 'decimal:2'];

    public function items() { return $this->hasMany(ExpenseTransactionItem::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }
}
