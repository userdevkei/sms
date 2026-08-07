<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = [
        'id', 'invoice_number', 'user_id', 'grade_level_id', 'academic_year', 'term',
        'total_amount', 'amount_paid', 'balance', 'status', 'due_date', 'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2', 'amount_paid' => 'decimal:2', 'balance' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function gradeLevel(): BelongsTo { return $this->belongsTo(GradeLevel::class); }
    public function generatedBy(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Recompute amount_paid/balance/status from actual payment records — the one place this logic lives. */
    public function recalculate(): void
    {
        $paid = $this->payments()->sum('amount');
        $this->amount_paid = $paid;
        $this->balance = $this->total_amount - $paid;

        $this->status = match (true) {
            $this->balance <= 0 => 'paid',
            $paid > 0 => 'partially_paid',
            default => 'unpaid',
        };

        $this->save();
    }
}
