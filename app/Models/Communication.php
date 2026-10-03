<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Communication extends Model
{
    use HasStringId, SoftDeletes, LogsActivity;

    protected $fillable = [
        'id', 'communication_template_id', 'channel', 'subject', 'body',
        'audience_type', 'audience_params', 'status',
        'send_at', 'recurrence_rule', 'next_run_at', 'last_run_at', 'run_count', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'audience_params' => 'array',
            'send_at'         => 'datetime',
            'next_run_at'     => 'datetime',
            'last_run_at'     => 'datetime',
        ];
    }

    public function template(): BelongsTo { return $this->belongsTo(CommunicationTemplate::class, 'communication_template_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function recipients(): HasMany { return $this->hasMany(CommunicationRecipient::class); }

    public function isRecurring(): bool { return filled($this->recurrence_rule); }

    public function scheduleNextRun(): void
    {
        $base = $this->next_run_at ?? now();

        $this->next_run_at = match ($this->recurrence_rule) {
            'daily'   => $base->copy()->addDay(),
            'weekly'  => $base->copy()->addWeek(),
            'monthly' => $base->copy()->addMonthNoOverflow(),
            default   => null,
        };

        $this->last_run_at = now();
        $this->run_count++;
        $this->status = $this->isRecurring() ? 'scheduled' : 'sent';
        $this->save();
    }

    public function finalizeRun(): void
    {
        $this->isRecurring()
            ? $this->scheduleNextRun()
            : $this->update(['status' => 'sent']);
    }
}
