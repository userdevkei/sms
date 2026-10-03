<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunicationTemplate extends Model
{
    use HasStringId, SoftDeletes, LogsActivity;

    /** Known system trigger keys, for the dropdown in the template form. Custom/manual templates leave trigger_key null. */
    public const TRIGGER_KEYS = [
        'admission.code_issued'         => 'Admission — continuation code issued',
        'admission.status_update'       => 'Admission — generic status update',
        'admission.submitted'           => 'Admission — application submitted',
        'admission.resubmitted'         => 'Admission — application resubmitted',
        'admission.changes_requested'   => 'Admission — changes requested',
        'admission.interview_scheduled' => 'Admission — interview scheduled',
        'admission.admitted'            => 'Admission — admitted',
        'admission.rejected'            => 'Admission — rejected',
        'finance.fee_balance_reminder'  => 'Finance — fee balance reminder',
    ];

    protected $fillable = [
        'id', 'trigger_key', 'name', 'channels', 'subject',
        'sms_body', 'email_body', 'whatsapp_body', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return ['channels' => 'array', 'is_active' => 'boolean'];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public static function forTrigger(string $key): ?self
    {
        return static::query()->where('trigger_key', $key)->where('is_active', true)->first();
    }

    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->channels ?? [], true);
    }

    /** Replace {{placeholder}} tokens in the given field with real values. */
    public function render(string $field, array $data): string
    {
        return preg_replace_callback(
            '/\{\{\s*(\w+)\s*\}\}/',
            fn ($m) => $data[$m[1]] ?? $m[0],
            (string) $this->{$field}
        );
    }
}
