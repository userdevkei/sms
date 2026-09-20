<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class IncomeTransactionItem extends Model
{
    use HasStringId, LogsActivity;

    protected $fillable = ['income_transaction_id', 'income_category_id', 'description', 'quantity', 'unit_price', 'amount'];
    protected $casts = ['quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'amount' => 'decimal:2'];

    public function category() { return $this->belongsTo(IncomeCategory::class, 'income_category_id'); }
    public function transaction() { return $this->belongsTo(IncomeTransaction::class, 'income_transaction_id'); }
}
