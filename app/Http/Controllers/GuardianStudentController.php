<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuardianStudentController extends Controller
{
    /** Live search for students not yet linked to this guardian. */
    public function search(Request $request, User $user): JsonResponse
    {
        $this->authorizeManage($user);

        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $fullName = "CONCAT_WS(' ', u.first_name, NULLIF(u.middle_name, ''), u.last_name)";

        $rows = DB::table('users as u')
            ->leftJoin('student_enrollments as e', function ($j) {
                $j->on('e.user_id', '=', 'u.id')->where('e.status', 'active')->whereNull('e.deleted_at');
            })
            ->leftJoin('grade_levels as g', 'g.id', '=', 'e.grade_level_id')
            ->leftJoin('streams as s', 's.id', '=', 'e.stream_id')
            ->whereNull('u.deleted_at')
            ->where('u.status', 'active')
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('role_users')
                    ->join('roles', 'roles.id', '=', 'role_users.role_id')
                    ->whereColumn('role_users.user_id', 'u.id')
                    ->where('roles.slug', 'student');
            })
            ->whereNotExists(function ($sub) use ($user) {
                $sub->select(DB::raw(1))
                    ->from('guardian_student as gs')
                    ->whereColumn('gs.student_id', 'u.id')
                    ->where('gs.guardian_id', $user->id);
            })
            ->where(function ($w) use ($q, $fullName) {
                $w->whereRaw("{$fullName} LIKE ?", ["%{$q}%"])
                    ->orWhere('u.userID', 'like', "%{$q}%");
            })
            ->orderBy('u.first_name')
            ->limit(15)
            ->selectRaw("u.id, u.userID, {$fullName} as name, g.name as grade, s.name as stream")
            ->get();

        return response()->json($rows);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManage($user);

        $data = $request->validate([
            'student_id'   => ['required', 'string', 'max:12'],
            'relationship' => ['nullable', 'string', 'max:30'],
        ]);

        $student = User::findOrFail($data['student_id']);
        abort_unless($student->roles()->where('slug', 'student')->exists(), 422, 'That user is not a student.');

        DB::transaction(function () use ($request, $user, $student, $data) {
            if ($request->boolean('is_primary')) {
                $this->clearPrimary($student->id);
            }

            $user->students()->syncWithoutDetaching([
                $student->id => [
                    'relationship' => $data['relationship'] ?? null,
                    'is_primary'   => $request->boolean('is_primary'),
                    'can_pay'      => $request->boolean('can_pay'),
                    'status'       => 'active',
                ],
            ]);
        });

        return back()->with('success', "{$student->full_name} linked to {$user->full_name}.");
    }

    public function update(Request $request, User $user, User $student): RedirectResponse
    {
        $this->authorizeManage($user);

        $data = $request->validate([
            'relationship' => ['nullable', 'string', 'max:30'],
            'status'       => ['required', 'in:active,inactive'],
        ]);

        $exists = DB::table('guardian_student')
            ->where('guardian_id', $user->id)->where('student_id', $student->id)->exists();
        abort_unless($exists, 404);

        DB::transaction(function () use ($request, $user, $student, $data) {
            if ($request->boolean('is_primary')) {
                $this->clearPrimary($student->id);
            }

            DB::table('guardian_student')
                ->where('guardian_id', $user->id)->where('student_id', $student->id)
                ->update([
                    'relationship' => $data['relationship'] ?? null,
                    'is_primary'   => $request->boolean('is_primary'),
                    'can_pay'      => $request->boolean('can_pay'),
                    'status'       => $data['status'],
                    'updated_at'   => now(),
                ]);
        });

        return back()->with('success', 'Link updated.');
    }

    public function destroy(User $user, User $student): RedirectResponse
    {
        $this->authorizeManage($user);

        DB::table('guardian_student')
            ->where('guardian_id', $user->id)->where('student_id', $student->id)->delete();

        return back()->with('success', "{$student->full_name} unlinked from {$user->full_name}.");
    }

    /** A student has at most one primary guardian. */
    private function clearPrimary(string $studentId): void
    {
        DB::table('guardian_student')->where('student_id', $studentId)->update(['is_primary' => false]);
    }

    private function authorizeManage(User $guardian): void
    {
        abort_unless(auth()->user()?->hasPermission('users.update'), 403);
        abort_unless($guardian->roles()->where('slug', 'guardian')->exists(), 422, 'This user is not a guardian.');
    }

    public function student(Request $request, User $child)
    {
        $student = $child; // already verified by guardian.student middleware

        $enrollment = StudentEnrollment::with(['gradeLevel', 'stream'])
            ->where('user_id', $student->id)->where('status', 'active')->first();

        $balance = (float) Invoice::where('user_id', $student->id)->sum('balance');
        $link    = $request->attributes->get('guardian_link');

        return view('guardian.student', compact('student', 'enrollment', 'balance', 'link'));
    }
}
