<?php

namespace App\Services\Admissions;

use App\Models\AdmissionApplication;
use App\Models\AdmissionEvent;
use App\Models\Role;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns an approved (and, where required, interview-passed) application into a student:
 * a User with the student role + an active StudentEnrollment for the chosen stream.
 */
class AdmissionMigrationService
{
    private const STUDENT_ROLE_SLUG = 'student';
    private const USER_ID_PREFIX    = 'STU';

    /** @throws \DomainException when the application isn't ready or the setup is incomplete */
    public function migrate(AdmissionApplication $application, string $streamId, ?string $enrolledOn, User $by): User
    {
        return DB::transaction(function () use ($application, $streamId, $enrolledOn, $by) {
            // Lock the row so two people can't migrate the same application twice.
            $app = AdmissionApplication::whereKey($application->id)->with('guardians')->lockForUpdate()->firstOrFail();

            if (! $app->canMigrate()) {
                throw new \DomainException('This application is not ready to be migrated (it must be approved, and its interview passed where one is required).');
            }

            $role = Role::where('slug', self::STUDENT_ROLE_SLUG)->first();
            if (! $role) {
                throw new \DomainException("The '".self::STUDENT_ROLE_SLUG."' role does not exist yet — create it before migrating applicants.");
            }

            $userId  = $this->nextUserId();
            $primary = $app->primaryGuardian();

            $student = User::create([
                'userID'        => $userId,
                'first_name'    => $app->first_name,
                'middle_name'   => $app->middle_name,
                'last_name'     => $app->last_name,
                'gender'        => $app->gender,
                'date_of_birth' => $app->date_of_birth,
                'citizenship'   => $app->citizenship,
                'county'        => $app->county,
                'sub_county'    => $app->sub_county,
                'ward'          => $app->ward,
                // Students often have no email of their own; a placeholder keeps the (unique) column valid.
                'email'         => $app->student_email ?: strtolower($userId).'@students.invalid',
                'phone_number'  => $primary?->phone ?: $app->contact_phone,
                'password'      => Str::password(16),   // hashed by the model cast; reset from the users screen
                'status'        => 'active',
            ]);

            $student->roles()->attach($role->id);

            StudentEnrollment::unguarded(fn () => StudentEnrollment::create([
                'user_id'        => $student->id,
                'grade_level_id' => $app->grade_level_id,
                'stream_id'      => $streamId,
                'academic_year'  => $app->academic_year,
                'status'         => 'active',
                'enrolled_on'    => $enrolledOn ?: now()->toDateString(),
                'notes'          => "Admitted via application {$app->reference}",
            ]));

            $app->forceFill([
                'status'           => 'admitted',
                'migrated_user_id' => $student->id,
                'migrated_at'      => now(),
                'migrated_by'      => $by->id,
            ])->save();

            AdmissionEvent::record($app, 'admitted', "Admitted. Admission number {$userId}.", true, $by->id);

            return $student;
        });
    }

    /**
     * ===================== INTEGRATION POINT =====================
     * How `users.userID` (the admission number) is generated. This mirrors a simple STU0001 sequence —
     * replace it with whatever your student import / IdGenerator uses so numbers stay consistent.
     */
    private function nextUserId(): string
    {
        $prefix = self::USER_ID_PREFIX;

        $last = User::withTrashed()
            ->where('userID', 'like', $prefix.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(userID, ?) AS UNSIGNED)) as n', [strlen($prefix) + 1])
            ->value('n');

        return $prefix.str_pad((string) (((int) $last) + 1), 4, '0', STR_PAD_LEFT);
    }
}
