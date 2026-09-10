<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;

class ExpenseTransactionItem extends Model
{
    use HasStringId;

    protected $fillable = ['id', 'expense_transaction_id', 'expense_category_id', 'description', 'quantity', 'unit_price', 'amount'];
    protected $casts = ['quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'amount' => 'decimal:2'];

    public function category() { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function transaction() { return $this->belongsTo(ExpenseTransactionItem::class, 'expense_transaction_id'); }
}
