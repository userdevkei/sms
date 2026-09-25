<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionApplication extends Model
{
    use HasStringId, SoftDeletes;

    protected $guarded = [];   // only ever filled from validated arrays

    protected $casts = [
        'date_of_birth'         => 'date',
        'submitted_at'          => 'datetime',
        'reviewed_at'           => 'datetime',
        'interview_at'          => 'datetime',
        'migrated_at'           => 'datetime',
        'has_sibling_in_school' => 'boolean',
        'needs_boarding'        => 'boolean',
        'needs_transport'       => 'boolean',
        'interview_required'    => 'boolean',
    ];

    /** Statuses in which the applicant can still edit. */
    public const EDITABLE = ['draft', 'changes_requested'];

    public const STEPS = [
        1 => 'Student',
        2 => 'Parent / Guardian',
        3 => 'Grade level',
        4 => 'Documents',
        5 => 'Review & submit',
    ];

    /** status => [label, bootstrap colour, icon, message shown to the applicant] */
    public const STATUS_META = [
        'draft'               => ['Draft',                'secondary', 'bi-pencil-square',    'Your application is in progress. Pick up where you left off.'],
        'submitted'           => ['Submitted',            'primary',   'bi-send-check',       'We have received your application. It is waiting to be reviewed.'],
        'changes_requested'   => ['Changes requested',    'warning',   'bi-exclamation-circle', 'The school needs a few changes before it can continue. See the note below.'],
        'approved'            => ['Approved',             'success',   'bi-patch-check',      'Your application has been approved.'],
        'interview_scheduled' => ['Interview scheduled',  'info',      'bi-calendar-event',   'An interview has been scheduled. Details are below.'],
        'interview_passed'    => ['Interview passed',     'success',   'bi-emoji-smile',      'Congratulations — the interview was successful. The school will finalise the admission.'],
        'interview_failed'    => ['Interview not passed', 'danger',    'bi-x-octagon',        'Unfortunately the interview was not successful. The school may contact you.'],
        'admitted'            => ['Admitted',             'success',   'bi-mortarboard',      'Welcome! The admission is complete.'],
        'rejected'            => ['Not successful',       'danger',    'bi-x-circle',         'Unfortunately the application was not successful.'],
    ];

    /* ------------------------------------------------------------ relations */
    public function guardians(): HasMany
    {
        return $this->hasMany(AdmissionGuardian::class)->orderByDesc('is_primary')->orderBy('created_at');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AdmissionAnswer::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AdmissionEvent::class)->latest();
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function migratedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'migrated_user_id');
    }

    /* ------------------------------------------------------------ helpers */
    public function getFullNameAttribute(): string
    {
        $name = trim("{$this->first_name} {$this->middle_name} {$this->last_name}");

        return $name !== '' ? preg_replace('/\s+/', ' ', $name) : 'Unnamed applicant';
    }

    public function primaryGuardian(): ?AdmissionGuardian
    {
        return $this->guardians->firstWhere('is_primary', true) ?? $this->guardians->first();
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE, true);
    }

    public function statusLabel(): string   { return self::STATUS_META[$this->status][0] ?? ucfirst($this->status); }
    public function statusColor(): string   { return self::STATUS_META[$this->status][1] ?? 'secondary'; }
    public function statusIcon(): string    { return self::STATUS_META[$this->status][2] ?? 'bi-circle'; }
    public function statusMessage(): string { return self::STATUS_META[$this->status][3] ?? ''; }

    public function canBookInterview(): bool
    {
        return $this->interview_required
            && in_array($this->status, ['approved', 'interview_scheduled', 'interview_failed'], true);
    }

    /** Approved and (no interview needed OR interview passed) and not yet migrated. */
    public function canMigrate(): bool
    {
        if ($this->migrated_user_id) {
            return false;
        }

        return ($this->status === 'approved' && ! $this->interview_required)
            || $this->status === 'interview_passed';
    }

    public static function generateReference(): string
    {
        $prefix = 'APP-'.now()->year.'-';

        $last = static::withTrashed()
            ->where('reference', 'like', $prefix.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(reference, ?) AS UNSIGNED)) as n', [strlen($prefix) + 1])
            ->value('n');

        return $prefix.str_pad((string) (((int) $last) + 1), 4, '0', STR_PAD_LEFT);
    }
}
