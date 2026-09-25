<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionGuardian extends Model
{
    use HasStringId, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_primary'   => 'boolean',
        'is_emergency' => 'boolean',
    ];

    public const RELATIONSHIPS = [
        'mother'   => 'Mother',
        'father'   => 'Father',
        'guardian' => 'Guardian',
        'sponsor'  => 'Sponsor',
        'other'    => 'Other',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplication::class, 'admission_application_id');
    }

    public function relationshipLabel(): string
    {
        return self::RELATIONSHIPS[$this->relationship] ?? ucfirst((string) $this->relationship);
    }
}
