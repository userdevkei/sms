<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class AdmissionAnswer extends Model
{
    use HasStringId, SoftDeletes;

    protected $guarded = [];

    public const DISK = 'local';   // private — documents are only served through authorised controllers

    public function application(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplication::class, 'admission_application_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(AdmissionRequirement::class, 'admission_requirement_id')->withTrashed();
    }

    public function hasFile(): bool
    {
        return $this->file_path && Storage::disk(self::DISK)->exists($this->file_path);
    }

    public function deleteFile(): void
    {
        if ($this->file_path) {
            Storage::disk(self::DISK)->delete($this->file_path);
        }
    }

    /** Human-friendly value for review screens. */
    public function displayValue(): string
    {
        $type = $this->requirement?->type;

        return match (true) {
            $type === 'file'     => (string) $this->file_name,
            $type === 'checkbox' => $this->value === '1' ? 'Yes' : 'No',
            default              => (string) $this->value,
        };
    }
}
