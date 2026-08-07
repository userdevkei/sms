<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentRequest;
use App\Models\GradeLevel;
use App\Models\StudentEnrollment;
use App\Models\User;

class EnrollmentController extends Controller
{
    public function create(User $student)
    {
        abort_unless($student->roles->pluck('slug')->contains('student'), 404);
        abort_if($student->currentEnrollment, 422, 'This student already has an active enrollment.');

        $gradeLevels = GradeLevel::query()->where('status', 'active')->with('educationLevel')->orderBy('sequence')->get();

        return view('curriculum.enrollments.create', compact('student', 'gradeLevels'));
    }

    public function store(StoreEnrollmentRequest $request, User $student)
    {
        abort_unless($student->roles->pluck('slug')->contains('student'), 404);
        abort_if($student->currentEnrollment, 422, 'This student already has an active enrollment.');

        $validated = $request->validated();

        StudentEnrollment::query()->create([
            'user_id'        => $student->id,
            'grade_level_id' => $validated['grade_level_id'],
            'stream_id'      => $validated['stream_id'] ?? null,
            'academic_year'  => $validated['academic_year'],
            'status'         => 'active',
            'enrolled_on'    => $validated['enrolled_on'] ?? now(),
        ]);

        return redirect()->route('students.profile', $student->id)->with('success', "{$student->full_name} was enrolled successfully.");
    }

    /** AJAX: streams belonging to a given grade level, for the dependent dropdown. */
    public function streamsForGrade(GradeLevel $gradeLevel)
    {
        return response()->json(
            $gradeLevel->streams()->where('status', 'active')->orderBy('name')->get(['id', 'name'])
        );
    }
}
