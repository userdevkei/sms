<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionEvent extends Model
{
    use HasStringId, SoftDeletes;

    protected $guarded = [];

    protected $casts = ['is_public' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(AdmissionApplication $application, string $type, string $message, bool $public = true, ?string $userId = null): self
    {
        return static::create([
            'admission_application_id' => $application->id,
            'type'      => $type,
            'message'   => $message,
            'is_public' => $public,
            'user_id'   => $userId,
        ]);
    }
}
