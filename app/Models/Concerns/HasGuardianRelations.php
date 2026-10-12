<?php


namespace App\Models\Concerns;

use App\Models\GuardianStudent;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasGuardianRelations
{
    /** For a student: all linked guardians. */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'guardian_student', 'student_id', 'guardian_id')
            ->using(GuardianStudent::class)
            ->withPivot(['relationship', 'is_primary', 'can_pay', 'status'])
            ->withTimestamps();
    }

    /** For a guardian: all linked students. */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'guardian_student', 'guardian_id', 'student_id')
            ->using(GuardianStudent::class)
            ->withPivot(['relationship', 'is_primary', 'can_pay', 'status'])
            ->withTimestamps();
    }

    /** Pivot-active students who also have an active enrollment. */
    public function activeStudents(): BelongsToMany
    {
        return $this->students()
            ->wherePivot('status', 'active')
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('student_enrollments')
                    ->whereColumn('student_enrollments.user_id', 'users.id')
                    ->where('student_enrollments.status', 'active')
                    ->whereNull('student_enrollments.deleted_at');
            });
    }

    public function isGuardian(): bool
    {
        return $this->roles()->where('name', 'guardian')->exists();
    }

    public function guardsStudent(string $studentId): bool
    {
        return $this->students()
            ->wherePivot('status', 'active')
            ->where('users.id', $studentId)
            ->exists();
    }
}
